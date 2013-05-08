<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Index extends Controller_Base {

	public function before()
	{
		parent::before();
		$format = $this->request->param('format');
		$this->view = View::factory($this->request->action().'/'.$format);
		$this->template->content = $this->view;
		if ($format != 'html')
		{
			$this->auto_render = FALSE;
		}
	}

	public function action_dates()
	{
		$this->template->selected_toplink = Route::url('dates');

		$year = $this->request->param('year');
		$month = $this->request->param('month');

		// Redirect to current month if no date given.
		if (is_null($year) && is_null($month))
		{
			$sql = "SELECT
				YEAR(MAX(date_and_time)) AS year,
				MONTH(MAX(date_and_time)) AS month
				FROM images
				GROUP BY date_and_time ORDER BY date_and_time DESC
				LIMIT 1";
			$last_entry = Database::instance()
				->query(Database::SELECT, $sql, TRUE)
				->current();
			if (!$last_entry) {
				$this->add_flash_message('No photos have yet been uploaded.', 'success');
				$this->request->redirect('upload');
			}
			$params = array('year'=>$last_entry->year, 'month'=>$last_entry->month);
			$this->request->redirect(Route::get('dates')->uri($params));
		}
		
		// Redirect to zero-padded dates if required.
		$redirect = false;
		if (strlen($year)!=4)
		{
			$year = str_pad($year, 4, '0', STR_PAD_LEFT);
			$redirect = true;
		}
		if (strlen($month)!=2)
		{
			$month = str_pad($month, 2, '0', STR_PAD_LEFT);
			$redirect = true;
		}
		if ($redirect)
		{
			$params = array('year'=>$year, 'month'=>$month);
			$this->request->redirect(Route::get('dates')->uri($params));
		}
		
		// Account for 'zero' date-parts as meaning 'unknown'.
//		if (!$month || stripos($month, 'unknown') !== FALSE)
//		{
//			$month_number = '00';
//		}
//		else
//		{
//			$month_number = $month ? date('m', strtotime("1 $month 2010")) : date('m');
//		}

		$this->template->bind_global('current_year', $year);
		$this->template->bind_global('current_month', $month);
		$this->template->current_toplink = Route::url('dates');

		$sql = "SELECT DATE_FORMAT(date_and_time, '%Y') AS year
			FROM images 
			GROUP BY YEAR(date_and_time)
			ORDER BY year DESC";
		$this->view->years = Database::instance()->query(Database::SELECT, $sql, TRUE);
		$sql = "SELECT DATE_FORMAT(date_and_time,'%m') AS month
			FROM images
			WHERE YEAR(date_and_time) = ".$year." 
			GROUP BY MONTH(date_and_time)
			ORDER BY month DESC";
		$this->view->months = Database::instance()->query(Database::SELECT, $sql, TRUE);

		$imgs = ORM::factory('images')
				->or_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 1)
				->or_where_close()
				->and_where(DB::expr('YEAR(date_and_time)'), '=', $year)
				->and_where(DB::expr('MONTH(date_and_time)'), '=', $month)
				->order_by('date_and_time', 'DESC');
		$this->view->photos = $imgs->find_all();
		
		// Title
		if ($this->view->current_year > 0 && $this->view->current_month > 0)
		{
			$this->title = date('F', strtotime('2010-'.$this->view->current_month.'-01'))
					.' '.$this->view->current_year;
		}
		elseif ($this->view->current_year > 0 && $this->view->current_month == '00')
		{
			$this->title = $this->view->current_year.' (Month Unknown)';
		}
		elseif ($this->view->current_year == '0000' && $this->view->current_month == 0)
		{
			$this->title = 'Year and Month Unknown';
		}
		elseif ($this->view->current_year == '0000' && $this->view->current_month > 0)
		{
			$this->title = date('F', strtotime('2010-'.$this->view->current_month.'-01')).' (Year Unknown)';
		}
		else
		{
			$this->title = 'Date Unknown';
		}
		
		$url = Route::url('dates', array('format'=>'pdf','year'=>$this->view->current_year,'month'=>$this->view->current_month), TRUE);
		$this->add_template_message(HTML::anchor($url, "Download a PDF album of these photos."), 'success');
		
		// Output PDF, via LaTeX
		if ($this->request->param('format')=='pdf')
		{
			$this->output_pdf(URL::title($this->title));
		}

	}

	public function action_tags()
	{
		$this->template->selected_toplink = Route::url('tags');

		// Get the tag IDs
		$this->view->current_tags = $this->request->param('tag_ids', '');
		$tags = Model_Tags::parse($this->view->current_tags);
		$included_tags = array();
		$excluded_tags = array();
		$canonical_tag_string = '';
		foreach ($tags as $tag=>$sign) {
			if ($sign == '-') {
				$excluded_tags[] = $tag;
			} else {
				$included_tags[] = $tag;
			}
			$canonical_tag_string .= $sign.$tag;
		}
		
		// Redirect to canonical tag URL
		if ($this->view->current_tags != $canonical_tag_string)
		{
			$url = Route::url('tags', array('tag_ids'=>$canonical_tag_string), TRUE);
			$this->request->redirect($canonical_tag_string);
		}

		// Get all photos.
		$photos = ORM::factory('images')
			->or_where_open()
			->where('auth_level_id', '<=', $this->user->auth_level)
			->or_where('auth_level_id', '=', 1)
			->or_where_close()
			->group_by('images.id');
		if (count($included_tags) > 0)
		{
			foreach ($included_tags as $included_tag) {
				$alias = uniqid('it_');
				$photos->join(array('image_tags', $alias))
					->on($alias.'.image_id', '=', 'images.id')
					->on($alias.'.tag_id', '=', DB::expr($included_tag));
			}
		}
		if (count($excluded_tags) > 0)
		{
			$exclude = DB::select()
				->from('image_tags')
				->where('image_id', '=', DB::expr('images.id'))
				->where('tag_id', 'IN', $excluded_tags);
			$photos->where(DB::expr('NOT EXISTS'), '', $exclude);
		}
		$this->view->photos = $photos->order_by('date_and_time')->find_all();
		
		// Get all tags.
		$all_tags = ORM::factory('Tags')
			->select(array('tags.id', 'id'))
			->select(array('tags.name', 'name'))
			->select(array(DB::expr('COUNT(DISTINCT it2.image_id)'), 'count'))
			->join('image_tags')->on('tags.id', '=', 'image_tags.tag_id')
			->join(array('images','i1'))->on('image_tags.image_id', '=', 'i1.id')
			->join(array('image_tags','it2'))->on('i1.id', '=', 'it2.image_id')
			->join(array('images','i2'))->on('image_tags.image_id', '=', 'i2.id')
			->and_where_open()
			->where('i1.auth_level_id', '<=', $this->user->auth_level)
			->or_where('i1.auth_level_id', '=', 1)
			->and_where_close()
			->and_where_open()
			->where('i2.auth_level_id', '<=', $this->user->auth_level)
			->or_where('i2.auth_level_id', '=', 1)
			->and_where_close()
			->order_by('tags.name')
			->group_by('tags.id'); // ->group_by('it2.image_id');
		if (count($included_tags) > 0)
		{
			/*foreach ($included_tags as $included_tag) {
				$alias = uniqid('it_');
				$photos->join(array('image_tags', $alias))
					->on($alias.'.image_id', '=', 'images.id')
					->on($alias.'.tag_id', '=', DB::expr($included_tag));
			}*/
			$all_tags->where('it2.tag_id', 'IN', $included_tags);
		}
		if (count($excluded_tags) > 0)
		{
			$all_tags->where('it2.tag_id', 'NOT IN', $excluded_tags);
		}
		
		$this->view->tags = $all_tags->find_all();

		// Get tag with max count
		$this->view->max = 0;
		foreach ($this->view->tags as $tag)
		{
			if ($tag->count > $this->view->max) $this->view->max = $tag->count;
		}
		
		$this->title = 'Tagged Photos';
		
		// Output PDF, via LaTeX
		if ($this->request->param('format')=='pdf')
		{
			$this->output_pdf($canonical_tag_string);
		}
	}
	
	protected function output_pdf($filename)
	{
		// Get LaTeX
		$tex = $this->view->render();

		// Write to temp file
		$dir = DATAPATH.'images'.DIRECTORY_SEPARATOR.'pdf';
		$filename = URL::title($this->user->name).'_'.$filename;
		$filepath = $dir.DIRECTORY_SEPARATOR.$filename;
		file_put_contents($filepath.'.tex', $tex);

		// Process to PDF
		$cmd = "pdflatex --output-directory=$dir $filepath.tex";
		exec("$cmd; makeindex $filepath.idx; $cmd; $cmd");

		// Send PDF to user
		//Kohana_debug::vars($this->response->headers());exit();
		$this->response->send_file($filepath.'.pdf', $filename.'.pdf', array('mime_type'=>'application/pdf'));

	}

	/**
	 * http://diveintomark.org/archives/2004/05/28/howto-atom-id
	 */
	/*public function action_feed($tag = FALSE)
	{
		$tag_clause = ($tag) ? "tags.name = ".Database::instance()->quote(urldecode($tag)) : '1';
		$this->template = View::factory('blog/feed');
		$this->template->entries = Database::instance()->query(Database::SELECT, "SELECT *
			FROM
				(SELECT
					images.id,
					'images' AS controller,
					DATE_FORMAT(date_and_time,'%Y-%m-%dT%H:%i:%s+08:00') as date_and_time,
					CONCAT('(Image #',images.id,')') AS title,
					caption AS summary
				FROM images
				  LEFT JOIN image_tags ON (image_tags.image_id=images.id)
				  LEFT JOIN tags ON (tags.id=image_tags.tag_id)
				WHERE auth_level_id = 1 AND $tag_clause
				ORDER BY date_and_time DESC
				LIMIT 10) as x
			UNION
				(SELECT
					journal_entries.id,
					'journal' AS controller,
					DATE_FORMAT(date_and_time,'%Y-%m-%dT%H:%i:%s+08:00') as date_and_time,
					IFNULL(journal_entries.title, CONCAT('(Journal entry #',journal_entries.id,')')) AS title,
					entry_text AS summary
				FROM journal_entries
				  LEFT JOIN journal_entry_tags ON (journal_entry_tags.journal_entry_id=journal_entries.id)
				  LEFT JOIN tags ON (tags.id=journal_entry_tags.tag_id)
				WHERE auth_level_id = 1 AND $tag_clause
				ORDER BY date_and_time DESC
				LIMIT 10)
			ORDER BY date_and_time DESC", TRUE
		);
		$this->template->tag = $tag;
		$this->request->headers['Content-Type'] = 'application/atom+xml';
	}*/

}

