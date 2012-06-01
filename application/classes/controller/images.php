<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Images extends Controller_Base {

	public function before()
	{
		parent::before();
		$this->template->title = 'Images';
		$this->template->pending_file_count = $this->get_pending_file_count();
	}

	public function action_index()
	{
		$year = $this->request->param('id');
		$sql = "SELECT DATE_FORMAT(date_and_time, '%Y') AS year
			FROM images
			GROUP BY YEAR(date_and_time)
			ORDER BY year ASC";
		$this->view->years = Database::instance()->query(Database::SELECT, $sql, TRUE);
		$this->view->year = (!$year) ? date('Y') : $year;
		$this->view->images = ORM::factory('images')
				->where(DB::expr('YEAR(date_and_time)'), '=', $this->view->year)
				->and_where_open()
				->where('auth_level_id', '>=', $this->user->auth_level)
				->or_where('auth_level_id', '=', 0)
				->and_where_close()
				->order_by('date_and_time', 'ASC')
				->find_all();
	}

	public function action_latex($year = NULL)
	{
		if ($this->user->auth_level < 10)
		{
			$this->add_flash_message('You are not allowed to generate albums.');
			$this->request->redirect('images');
		}
		if (!is_numeric($year) || $year < 1111 || $year > 9999)
		{
			$this->add_flash_message('Please specify a year for which to export an album.');
			$this->request->redirect('blog');
		}
		$images = ORM::factory('images')
				->where(DB::expr('YEAR(date_and_time)'), '=', $year)
				->find_all();
		$latex = View::factory('images/latex')
				->bind('images', $images)
				->bind('year', $year)
				->render();
		$filename = DATAPATH.'/images/albums/'.$year.'.tex';
		file_put_contents($filename, $latex);
		$this->add_flash_message('The <span class="latex">L<sup>a</sup>&Tau;<sub>&epsilon;</sub>&Chi;</span> file for the '.$year.' Album has been written to disk.', 'success');
		$this->request->redirect('blog/'.$year);
	}

	public function action_render($id, $size = 'view')
	{
		$image = ORM::factory('images')
				->or_where_open()
				->where('auth_level', '<=', $this->user->auth_level)
				->or_where('auth_level', '=', 0)
				->or_where_close()
				->find($id);
		if ($size != 'full' && $size != 'thumb')
			$size = 'view';
		if ($image->loaded())
		{
			$filename = DATAPATH."images/$size/$image.jpg";
			if (!file_exists($filename))
			{
				$image->make_smaller_versions();
			}
			$this->request->send_file($filename, NULL, array('inline'=>TRUE));

//			$this->request->headers['Content-Type'] = "image/jpeg";
//			$this->request->headers['Content-Disposition'] = "attachment; filename=\"$filename\"";
//			$this->request->headers['X-Sendfile'] = $filename;
//			$this->request->headers['Content-length'] = filesize($filename);
//			$this->request->response = '';
		}
		else
		{
			$this->add_template_message("Image #$id could not be found; "
					."perhaps you do not have permission to view it?");
		}

//		$image = new Model_Image($id);
//		$user = new Model_User();
//
//		if ( $image->auth_level == 0 || Auth::instance()->logged_in($image->required_role) )
//		{
//			$this->request->send_file($image->get_filename($size), NULL, array('inline'=>TRUE));
//		} else
//		{
//			exit('Access Denied');
//		}

		/* $filename = DATAPATH."/images/$size/$id.jpg";
		  if (file_exists($filename))
		  {
		  $image_data = $db->fetchRow("SELECT * FROM images WHERE id='".$db->esc($_GET['id'])."' LIMIT 1");
		  if ( $image_data['auth_level'] == 0
		  || $image_data['auth_level'] <= $auth->getAuthData('auth_level') )
		  {
		  $length = filesize($filename);
		  header('Content-type: image/jpeg');
		  header('Content-Length: '.$length);
		  header('Content-Disposition: inline; filename="image-'.basename($filename).'"');
		  readfile($filename);
		  die();
		  } else
		  {
		  die("Access denied.");
		  }
		  } else
		  {
		  exit("Image not found: $filename");
		  } */
	}

	public function action_edit($id)
	{
		if ($this->user->auth_level < 10)
		{
			$this->add_flash_message('You are not allowed to edit images.');
			$this->request->redirect('images');
		}
		$this->view->image = ORM::factory('images', $id);
		if (!$this->view->image->loaded())
		{
			$this->add_flash_message('The requested image could not be found.', 'error');
			$this->request->status = 404;
			$this->request->redirect('images');
		}
		$this->template->title = $this->title = "Editing Image #".$this->view->image->id;
		$this->jquery = TRUE;

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
				->order_by('auth_level', 'DESC')->order_by('name', 'ASC')
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

		/*
		  $fullFilePath = DATADIR.'/images/full/'.$this_image['id'].'.jpg';
		  if (file_exists($fullFilePath))
		  {
		  if ($exifData = @exif_read_data($fullFilePath))
		  {
		  foreach ($exifData as $name=>$value)
		  {
		  $page->addBodyContent("<tr><th>$name</th><td>$value</td></tr>");
		  }
		  } else
		  {
		  $page->addBodyContent("<tr><th>Error:</th><td>Could not read EXIF data.</td></tr>");
		  }
		  }
		  $page->addBodyContent("</table></div>");
		 *
		 */
	}

	/* public function action_upload()
	  {
	  if (isset($_POST['upload_image']))
	  {
	  require_once "HTTP/Upload.php";
	  $uploadTo = DATADIR.'/images/IN';
	  checkDirectory($uploadTo);
	  $upload = new HTTP_Upload('en');
	  $file = $upload->getFiles('image');
	  if ($file->isValid())
	  {
	  $moved = $file->moveTo($uploadTo, false);
	  if (PEAR::isError($moved))
	  {
	  $page->addBodyContent("<p class='message error'>Could not move uploaded file.<br />".$moved->getMessage()."</p>");
	  $page->display();
	  die();
	  }
	  } elseif ($file->isError())
	  {
	  $page->addBodyContent("<p class='message error'>File is erroneous.<br />".$file->getMessage()."</p>");
	  $page->display();
	  die();
	  } elseif ($file->isMissing())
	  {
	  $page->addBodyContent("<p class='message error'>File is missing.<br />".$file->getMessage()."</p>");
	  } else
	  {
	  die(var_dump($file->getProp()));
	  $uploadedImageFilename = "$uploadTo/".$file->getProp('tmp_name');
	  if (!realpath($uploadedImageFilename))
	  {
	  $page->addBodyContent("<p class='message error'>Can't see $uploadedImageFilename</p>");
	  $page->display();
	  die();
	  }
	  $page->addBodyContent("<p class='notice message'>Uploading $uploadedImageFilename</p>");
	  $id = importImage($uploadedImageFilename);
	  header("Location:?action=edit_image&id=$id");
	  die();
	  }
	  }

	  } */

	/**
	 * Process a single image from the IN directory.
	 */
	public function action_process()
	{
		if ($this->user->auth_level < 10 && !Kohana::$is_cli)
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
			$image = ORM::factory('images');
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
			$this->request->redirect('images/edit/'.$image->id.'#form');
		}
		else
		{
			exit();
		}
	}

	public function get_pending_file_count()
	{
		// Pending file count:
		$inDir = DATAPATH.'images/IN';
		$pendingCount = 0;
		if (is_dir($inDir))
		{
			$pendingCount = count(preg_grep("/^[^\.]/", scandir($inDir)));
		}
		if ($pendingCount > 0 && $this->user->auth_level >= 10)
		{
			$this->add_template_message("$pendingCount images remain to be "
					."accessioned.  ".html::anchor('images/process', 'Process one.'));
		}
	}

	/* public function get_upload_form()
	  {
	  // Upload form:
	  $form = new HTML_QuickForm('','post',$_SERVER['PHP_SELF']);
	  $form->setMaxFileSize(1020 * 1024 * 10);
	  $file_element = new HTML_QuickForm_file('image', null, array('size'=>80));
	  $submit_element = new HTML_QuickForm_submit('upload_image','Upload');
	  $uploadLabel = 'Upload (maximum '.ini_get('upload_max_filesize').'): ';
	  $form->addGroup(array($file_element,$submit_element), null, $uploadLabel);
	  $page->addBodyContent($form->toHtml());
	  } */

	public function action_rotate($id, $degrees)
	{
		$image = ORM::factory('images', $id);
		if ($image->loaded() && $this->user->auth_level >= 10 && $degrees > 0)
		{
			$image->rotate($degrees);
			$this->request->redirect("images/edit/$id#form");
		}
	}

	public function action_delete($id = NULL)
	{
		if ($id == NULL || !is_numeric($id) || $this->user->auth_level < 10)
		{
			$this->add_template_message('Access Denied', 'error');
			$this->request->response = '';
			return;
		}
		$image = ORM::factory('Images', $id);
		$this->view->image = $image;
		if (Arr::get($_GET, 'confirm', FALSE) == 'yes')
		{
			$month_name = $image->month_name();
			$year = $image->year();
			$image->delete();
			$this->add_template_message("Image #$id has been deleted", 'success');
			$this->request->redirect("blog/$year/$month_name");
		}
	}

	public function action_save()
	{

		if (isset($_POST['save_image']) && $this->user->auth_level >= 10)
		{
			$image = ORM::factory('images', $_POST['id']);

			// Save tags
			$tags = array_unique(array_map('trim', explode(',', $_POST['tags'])));
			DB::delete('tags_to_images')->where('image', '=', $image->id)->execute();
			foreach ($tags as $tag_title)
			{
				if (!empty($tag_title))
				{
					$tag = ORM::factory('tags', array('title'=>$tag_title));
					if (!$tag->loaded())
					{
						$tag->title = $tag_title;
					}
					$tag->save();
					$image->add('tags', $tag);
				}
			}

			// Save image data.
			if ($image->save_from_post())
			{
				$this->add_flash_message('Image #'.$image->id.' saved.', 'success');
				if (isset($_POST['save_and_edit']))
				{
					$this->request->redirect('images/edit/'.$image->id.'#form');
				}
				if (isset($_POST['save_and_view']))
				{
					$this->request->redirect('blog/'.$image->year().'/'.$image->month_name().'#'.$image->id);
				}
				if (isset($_POST['save_and_process']))
				{
					$this->request->redirect('images/process');
				}
				if (isset($_POST['save_and_next']))
				{
					$im = ORM::factory('images')->where('id', '>', $image->id)->order_by('id', 'ASC')->limit(1)->find();
					$this->request->redirect('images/edit/'.$im->id.'#form');
				}
			}
		}
	}

	public function action_view($id = NULL)
	{
		// If no image ID specified, redirect.
		if ($id == NULL)
			$this->request->redirect('images');
		$this->view->image = ORM::factory('images')
				->where('id', '=', $id)
				->and_where_open()
				->where('auth_level', '<=', $this->user->auth_level)
				->or_where('auth_level', '=', 0)
				->and_where_close()
				->find();
		if (!$this->view->image->loaded())
		{
			$this->add_flash_message('The requested image could not be found.', 'error');
			$this->request->redirect('images');
		}
		$this->template->title = "Image #".$this->view->image->id;
	}

}