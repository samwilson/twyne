<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Index extends Controller_Base {

	public function before()
	{
		parent::before();
		//$false = FALSE;
		//$this->template->bind_global('current_year', $false);
		//$this->template->bind_global('current_month_name', $false);
		//$this->template->bind_global('current_month_number', $false);
	}

	public function action_index()
	{
		$this->template->selected_toplink = Route::url('dates');

		$year = $this->request->param('year');
		$month = $this->request->param('month');

		// Redirect to current month if no date given.
		if (!$year && !$month)
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
	}

	public function action_tag()
	{
		$this->view = View::factory('blog/index');
		$this->template->content = $this->view;

		$sql = "SELECT DATE_FORMAT(date_and_time, '%Y') AS year FROM images
			UNION SELECT DATE_FORMAT(date_and_time, '%Y') AS year FROM journal_entries
			GROUP BY YEAR(date_and_time) ORDER BY year DESC";
		$this->view->years = Database::instance()->query(Database::SELECT, $sql, TRUE);

		$tag = urldecode($this->request->param('tag', FALSE));

		$imgs = ORM::factory('images')
				->or_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 1)
				->or_where_close()
				->join('image_tags')->on('image_id', '=', 'images.id')
				->join('tags')->on('tag_id', '=', 'tags.id')
				->where('name', 'LIKE', $tag)
				->find_all();
		$images = array();
		$item_id = 1;
		foreach ($imgs as $img)
		{
			$images[$img->date_and_time.$item_id.'i'] = $img;
			$item_id++;
		}
		$jes = ORM::factory('JournalEntries')
				->or_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 1)
				->or_where_close()
				->join('journal_entry_tags')->on('journal_entry_id', '=', 'journalentries.id')
				->join('tags')->on('tag_id', '=', 'tags.id')
				->where('tags.name', 'LIKE', $tag)
				->find_all();
		$journal_entries = array();
		foreach ($jes as $je)
		{
			$journal_entries[$je->date_and_time.$item_id.'je'] = $je;
			$item_id++;
		}
		$this->view->items = array_merge($images, $journal_entries);
		ksort($this->view->items);
		
		$this->title = 'All items tagged &lsquo;'.$tag.'&rsquo;';
		$this->template->tag = $tag;
	}

	/**
	 * http://diveintomark.org/archives/2004/05/28/howto-atom-id
	 */
	public function action_feed($tag = FALSE)
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
	}

}

