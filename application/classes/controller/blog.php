<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Blog extends Controller_Base {

	public function before()
	{
		parent::before();
		$false = FALSE;
		$this->template->bind_global('current_year', $false);
		$this->template->bind_global('current_month_name', $false);
		$this->template->bind_global('current_month_number', $false);
	}

	public function action_index()
	{

		$year = $this->request->param('year', date('Y'));
		$month_name = $this->request->param('month', date('F'));

		// Redirect to current month if no date given.
		if (!$year && !$month_name)
		{
			$last_entry = Database::instance()->query(Database::SELECT, "SELECT
					(SELECT MAX(date_and_time) AS date_and_time
					FROM images
					GROUP BY date_and_time ORDER BY date_and_time DESC
					LIMIT 1) AS date_and_time
				UNION
					(SELECT MAX(date_and_time) AS date_and_time
					FROM journal_entries 
					GROUP BY date_and_time ORDER BY date_and_time DESC
					LIMIT 1)
				ORDER BY date_and_time DESC LIMIT 1", TRUE
			)->current();
			//$this->add_flash_message("(This blog is in <em>chronological</em> order; the most recent items are at the bottom.)", 'success');
			$this->request->redirect('blog/'.date('Y/F', strtotime($last_entry->date_and_time)));
		}
		if (!$month_name || stripos($month_name, 'unknown') !== FALSE)
		{
			$month_number = '00';
		}
		else
		{
			$month_number = $month_name ? date('m', strtotime("1 $month_name 2010")) : date('m');
		}

		$this->template->bind_global('current_year', $year);
		$this->template->bind_global('current_month_name', $month_name);
		$this->template->bind_global('current_month_number', $month_number);
		$this->template->current_toplink = 'blog';

		$sql = "SELECT DATE_FORMAT(date_and_time, '%Y') AS year FROM images 
            UNION SELECT DATE_FORMAT(date_and_time, '%Y') AS year FROM journal_entries 
            GROUP BY YEAR(date_and_time) ORDER BY year DESC";
		$this->view->years = Database::instance()->query(Database::SELECT, $sql, TRUE);
		$this->view->months = Database::instance()->query(
				Database::SELECT, "
            SELECT DATE_FORMAT(date_and_time,'%m') AS month FROM images WHERE YEAR(date_and_time) = ".$this->view->current_year." 
            UNION 
            SELECT DATE_FORMAT(date_and_time, '%m') AS month FROM journal_entries WHERE YEAR(date_and_time) = ".$this->view->current_year." 
			GROUP BY MONTH(date_and_time) ORDER BY month DESC
            ", TRUE
		);


		$imgs = ORM::factory('images')
				->or_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 1)
				->or_where_close()
				->and_where(DB::expr('YEAR(date_and_time)'), '=', $this->view->current_year)
				->and_where(DB::expr('MONTH(date_and_time)'), '=', $this->view->current_month_number)
				->find_all();
		$images = array();
		$item_id = 1;
		foreach ($imgs as $img)
		{
			$images[$img->date_and_time.' '.$item_id] = $img;
			$item_id++;
		}
		$jes = ORM::factory('JournalEntries')
				->or_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 1)
				->or_where_close()
				->and_where(DB::expr('YEAR(date_and_time)'), '=', $this->view->current_year)
				->and_where(DB::expr('MONTH(date_and_time)'), '=', $this->view->current_month_number)
				->find_all();
		$journal_entries = array();
		foreach ($jes as $je)
		{
			$journal_entries[$je->date_and_time.' '.$item_id] = $je;
			$item_id++;
		}
		$this->view->items = array_merge($images, $journal_entries);
		ksort($this->view->items);

		// Title
		if ($this->view->current_year > 0 && $this->view->current_month_number > 0)
		{
			$this->title = date('F', strtotime('2010-'.$this->view->current_month_number.'-01'))
					.' '.$this->view->current_year;
		}
		elseif ($this->view->current_year > 0 && $this->view->current_month_number == '00')
		{
			$this->title = $this->view->current_year.' (Month Unknown)';
		}
		elseif ($this->view->current_year == '0000' && $this->view->current_month_number == 0)
		{
			$this->title = 'Year and Month Unknown';
		}
		elseif ($this->view->current_year == '0000' && $this->view->current_month_number > 0)
		{
			$this->title = date('F', strtotime("2010-$this->view->current_month_number-01")).' (Year Unknown)';
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

