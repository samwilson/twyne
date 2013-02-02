<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Image extends Controller_Base {

	public function before()
	{
		parent::before();
		$this->template->title = 'Photos';
	}

	public function action_latex()
	{
		$this->view = View::factory('latex');
//		$id = $this->request->param('id');
//		$images = ORM::factory('images')
//				->where(DB::expr('YEAR(date_and_time)'), '=', $year)
//				->find_all();
//		$latex = View::factory('images/latex')
//				->bind('images', $images)
//				->bind('year', $year)
//				->render();
//		$filename = DATAPATH.'/images/albums/'.$year.'.tex';
//		file_put_contents($filename, $latex);
//		$this->add_flash_message('The <span class="latex">L<sup>a</sup>&Tau;<sub>&epsilon;</sub>&Chi;</span> file for the '.$year.' Album has been written to disk.', 'success');
	}

	public function action_render()
	{
		$id = $this->request->param('id');
		$size = $this->request->param('size');
		if ($size != 'full' && $size != 'thumb')
		{
			$size = 'view';
		}
		$image = ORM::factory('Images')
				->where('id', '=', $id)
				->and_where_open()
				->where('auth_level_id', '<=', $this->user->auth_level_id)
				->or_where('auth_level_id', '=', 1)
				->and_where_close()
				->find();
		if ($image->loaded())
		{
			$filename = DATAPATH."images/$size/$image.jpg";
			if (!file_exists($filename))
			{
				$image->make_smaller_versions();
			}
			$this->response->send_file($filename, NULL, array('inline'=>TRUE));
		}
		else
		{
			$this->add_template_message("Image #$id could not be found; "
					."perhaps you do not have permission to view it?");
		}
	}

	public function action_edit()
	{
		$id = $this->request->param('id');
		if ($this->user->auth_level_id < 10)
		{
			$this->add_flash_message('You are not allowed to edit images.');
			$this->request->redirect('images');
		}
		$this->view->image = ORM::factory('images', $id);
		if (!$this->view->image->loaded())
		{
			$this->add_flash_message('The requested image could not be found.', 'error');
			$this->response->status(404);
			$this->response->body('Not Found');
		}
		$this->template->title = $this->title = "Editing Image #".$this->view->image->id;
		$this->jquery = TRUE;

		// Auth Levels
		$this->view->auth_levels = array();
		$auth_levels = ORM::factory('AuthLevels')->order_by('id', 'ASC')->find_all();
		foreach ($auth_levels as $auth_level)
		{
			$this->view->auth_levels[$auth_level->id] = $auth_level->id.': '.$auth_level->name;
		}
		
		// Licences
		$this->view->licences = array();
		$licences = ORM::factory('Licences')->find_all();
		foreach ($licences as $licence)
		{
			$this->view->licences[$licence->id] = $licence->name;
		}

		// Authors
		$this->view->people = array();
		$people = ORM::factory('People')
				->order_by('auth_level_id', 'DESC')->order_by('name', 'ASC')
				->find_all();
		foreach ($people as $person)
		{
			$this->view->people[$person->id] = $person->name;
		}

		$this->view->chronology_prev = ORM::factory('images')
						->where('date_and_time', '<', $this->view->image->date_and_time)
						->and_where('id', '!=', $this->view->image->id)
						->order_by('date_and_time', 'DESC')->limit(1)->find();
		$this->view->chronology_next = ORM::factory('images')
						->where('date_and_time', '>', $this->view->image->date_and_time)
						->and_where('id', '!=', $this->view->image->id)
						->order_by('date_and_time', 'DESC')->limit(1)->find();

		$this->view->accession_prev = ORM::factory('images')
						->where('id', '<', $this->view->image->id)
						->order_by('id', 'DESC')->limit(1)->find();
		$this->view->accession_next = ORM::factory('images')
						->where('id', '>', $this->view->image->id)
						->order_by('id', 'ASC')->limit(1)->find();
	}

	public function action_upload()
	{
		$this->template->selected_toplink = Route::url('upload');
		$this->template->title = 'Upload Photos';
		if ($this->user->auth_level->id < 10)
		{
			$this->add_template_message('You are not allowed to upload photos.');
			$this->template->content = null;
			return;
		}
		$this->view->max_file_size = (ini_get('upload_max_filesize')) ? ini_get('upload_max_filesize') : '10M';
		$this->view->pending_files = ORM::factory('Images')->get_pending();
		if (isset($_FILES['uploaded_file']))
		{
			$file = $_FILES['uploaded_file'];
			if (Upload::not_empty($file) AND Upload::valid($file) AND Upload::size($file, $this->view->max_file_size)) {
				Upload::save($file);
				$this->add_flash_message($file['name'].' uploaded', 'success');
				$this->request->redirect('upload');
			} else {
				switch ($file['error'])
				{
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$msg = 'The uploaded file exceeds the permitted size of '.$this->view->max_file_size.'.';
						break;
					case UPLOAD_ERR_PARTIAL:
						$msg = 'The uploaded file was only partially uploaded.';
						break;
					case UPLOAD_ERR_NO_FILE:
						$msg = 'No file was uploaded.';
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$msg = 'The temporary upload directory could not be found.';
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$msg = 'Failed to write file to disk.';
						break;
					case UPLOAD_ERR_EXTENSION:
						$msg = 'A PHP extension prevented the upload of this file.';
						break;
					default:
						$msg = 'An indeterminate error occured, that was not do to with any of the standard errors.';
				}
				$this->add_template_message('Upload error: '.$msg, 'error');
			}
		}
		elseif ($this->request->param('filename'))
		{
			$filename = Upload::$default_directory.DIRECTORY_SEPARATOR.$this->request->param('filename');
			if (file_exists($filename))
			{
				$image = ORM::factory('Images');
				$image->author = $this->user;
				$image->import($filename);
				$this->request->redirect($image->id.'/edit#form');
			}
		}
	}

	/**
	 * Process a single image from the IN directory.
	 */
	public function action_process()
	{
		if ($this->user->auth_level_id < 10 && !Kohana::$is_cli)
		{
			$this->add_flash_message('You are not allowed to process images.');
			$this->request->redirect('images');
		}
		$images_in_dir = DATAPATH.'images/IN';
		foreach (scandir($images_in_dir) as $file)
		{
			if (substr($file, 0, 1) == '.' || is_dir($images_in_dir.'/'.$file))
			{
				continue;
			}
			$fullname = $images_in_dir.'/'.$file;
			$image = ORM::factory('Images');
			$image->author = $this->user;
			$image->import($fullname);
			break;
		}
		if (!isset($image) && !Kohana::$is_cli)
		{
			$this->add_template_message('Nothing processed.');
			return;
		}
		if (!Kohana::$is_cli)
		{
			$this->request->redirect($image->id.'/edit#form');
		}
		else
		{
			exit();
		}
	}

	public function action_rotate()
	{
		$id = $this->request->param('id');
		$degrees = $this->request->param('degrees');
		$image = ORM::factory('images', $id);
		if ($image->loaded() && $this->user->is_main_user() && $degrees > 0)
		{
			$image->rotate($degrees);
			$url = Route::url('image', array('action'=>'edit', 'id'=>$id), TRUE);
			$this->request->redirect("$url#form");
		}
	}

	public function action_delete()
	{
		$id = $this->request->param('id');
		if ($id == NULL || !is_numeric($id) || !$this->user->is_main_user())
		{
			$this->add_template_message('Access Denied', 'error');
			$this->request->response = '';
			return;
		}
		$image = ORM::factory('Images', $id);
		$this->view->image = $image;
		if (Arr::get($_GET, 'confirm', FALSE) == 'yes')
		{
			$params = array(
				'year' => $image->year(),
				'month' => $image->month_number(),
			);
			$url = Route::url('dates', $params);
			$image->delete();
			$this->add_flash_message("Image #$id has been deleted", 'success');
			$this->request->redirect($url);
		}
	}

	public function action_save()
	{

		if (isset($_POST['save_image']) && $this->user->auth_level_id >= 10)
		{
			$image = ORM::factory('images', $_POST['id']);

			// Save tags
			$tags = array_unique(array_map('trim', explode(',', $_POST['tags'])));
			DB::delete('image_tags')->where('image_id', '=', $image->id)->execute();
			foreach ($tags as $tag_name)
			{
				if (!empty($tag_name))
				{
					$tag = ORM::factory('tags')->where('name', '=', $tag_name)->find();
					if (!$tag->loaded())
					{
						$tag->name = $tag_name;
					}
					$tag->save();
					$image->add('tags', $tag);
				}
			}

			// Save image data.
			$image->date_and_time = $_POST['date_and_time'];
			$image->caption = $_POST['caption'];
			$image->auth_level_id  = $_POST['auth_level_id'];
			$image->author_id  = $_POST['author_id'];
			$image->licence_id   = $_POST['licence_id'];
			if ($image->save())
			{
				$this->add_flash_message('Image #'.$image->id.' saved.', 'success');
				if (isset($_POST['save_and_edit']))
				{
					$url = Route::url('image',array('action'=>'edit', 'id'=>$image->id), TRUE).'#form';
				}
				if (isset($_POST['save_and_view']))
				{
					$url = Route::url('view',array('id'=>$image->id), TRUE);
				}
				if (isset($_POST['save_and_process']))
				{
					$url = Route::url('process', NULL, TRUE);
				}
				if (isset($_POST['save_and_next']))
				{
					$im = ORM::factory('images')
						->where('id', '>', $image->id)
						->order_by('id', 'ASC')
						->limit(1)
						->find();
					if ($im->loaded()) {
						$url = Route::url('image', array('action'=>'edit','id'=>$im->id), TRUE);
						$this->request->redirect($url.'#form');
					} else {
						$url = '';
					}
				}
				$this->request->redirect($url);
			}
		}
	}

	public function action_view()
	{
		$id = $this->request->param('id');
		// If no image ID specified, redirect.
		if ($id == NULL)
		{
			$this->request->redirect('images');
		}
		if ($this->request->param('format')=='pdf')
		{
			$this->view = View::factory('latex_single');
			$this->auto_render = FALSE;
		}
		$this->view->image = ORM::factory('images')
			->where('id', '=', $id)
			->and_where_open()
			->where('auth_level_id', '<=', $this->user->auth_level_id)
			->or_where('auth_level_id', '=', 1)
			->and_where_close()
			->find();
		if (!$this->view->image->loaded())
		{
			$this->response->status(404);
			$this->add_template_message('The requested image could not be found.');
			$this->template->content = NULL;
		}
		$this->template->title = "Image #".$this->view->image->id;
		
		if ($this->request->param('format')=='pdf')
		{
			$tex = $this->view->render();
			DATAPATH.'images'.
			file_put_contents($filename, $tex);
			
		}
	}

}