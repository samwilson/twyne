<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Journal extends Controller_Base {

	public function before()
	{
		parent::before();
		$this->template->title = 'Journal';
		$this->jquery = TRUE;
	}

	public function action_edit()
	{
		$id = $this->request->param('id');
		if ($this->user->auth_level->id < 10)
		{
			$this->add_flash_message('You are not allowed to edit things.');
			$this->request->redirect('/');
		}
		$this->selected_toplink = '/journal/edit';
		$entry = ORM::factory('JournalEntries', $id);
		if (!$entry->loaded())
		{
			$entry->date_and_time = date('Y-m-d H:i:s');
			$entry->auth_level_id = 10;
		}
		$this->view->entry = $entry;
		if (isset($_POST['save']))
		{
			if (empty($_POST['title']))
			{
				$_POST['title'] = NULL;
			}
			$expected = array('date_and_time', 'title', 'auth_level_id', 'entry_text');
			$entry->values($_POST, $expected);
			if ($entry->check())
			{
				$entry->save();

				// Save tags
				$tags = array_unique(array_map('trim', explode(',', $_POST['tags']))); // @TODO remove empties here
				DB::delete('journal_entry_tags')->where('journal_entry_id', '=', $entry->id)->execute();
				foreach ($tags as $tag_name)
				{
					if (!empty($tag_name))
					{
						$tag = ORM::factory('tags')
								->where('name', 'LIKE', $tag_name)
								->find();
						if (!$tag->loaded())
						{
							$tag->name = $tag_name;
						}
						$tag->save();
						$entry->add('tags', $tag);
					}
				}

				$this->add_flash_message("Journal entry saved", "success");
				$this->request->redirect('journal/view/'.$entry->id);
			}
		}
	}

	public function action_index()
	{
		$this->view->year = $this->request->param('id', date('Y'));
		$sql = "SELECT DATE_FORMAT(date_and_time, '%Y') AS year
                FROM journal_entries
                GROUP BY YEAR(date_and_time)
                ORDER BY year ASC";
		$this->view->years = Database::instance()->query(Database::SELECT, $sql, TRUE);
		$this->view->journal_entries = ORM::factory('JournalEntries')
				->where(DB::expr('YEAR(date_and_time)'), '=', $this->view->year)
				->and_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 0)
				->and_where_close()
				->order_by('date_and_time', 'ASC')
				->find_all();
		if (count($this->view->journal_entries) == 0)
		{
			$this->add_template_message('No journal entries found.', 'success');
		}
	}

	public function action_latex($year = null)
	{
		if ($this->user->auth_level < 10 && !Kohana::$is_cli)
		{
			$this->add_flash_message('Access Denied');
			$this->request->redirect('/');
		}
		if (!is_numeric($year) || $year < 1111 || $year > 9999)
		{
			$this->add_flash_message(
					'Please specify a year for which to export the journal '
					.'('.$year.' is not valid).'
			);
			//$this->request->redirect('/');
		}
		//$this->request->headers['Content-Type'] = 'text/plain';
		//$this->template = View::factory('journal/latex');
		//$this->template->year = $year;
		$entries = ORM::factory('JournalEntries')
				->where(DB::expr('YEAR(date_and_time)'), '=', $year)
				->find_all();

		$latex = View::factory('journal/latex')
				->bind('entries', $entries)
				->bind('year', $year)
				->render();
		$filename = DATAPATH.'/journal/'.$year.'.tex';
		file_put_contents($filename, $latex);
		exit("LaTeX journal for $year has been written to $filename\n");
	}

	public function action_view()
	{
		$id = $this->request->param('id');
		$entry = ORM::factory('JournalEntries')
				->where('id', '=', $id)
				->and_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 0)
				->and_where_close()
				->find();
		if (!$entry->loaded())
		{
			$this->response->status(404);
			$this->response->body('404 Not Found');
			//$this->response->headers('Content-type', 'text/plain');
			//$this->auto_render = FALSE;
			//$this->response
		}
		$this->view->entry = $entry;
		$this->controller_view->title = (!empty($entry->title)) ? $entry->title : 'Journal Entry #'.$entry->id;
		$this->template->title = $this->controller_view->title;
	}

}
