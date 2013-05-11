<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_People extends Controller_Base {

	public function action_index()
	{
		if ($this->user->auth_level_id < 10)
		{
			$this->controller_view->content = NULL;
			$this->add_flash_message('Access Denied');
			$this->redirect(URL::site('/'));
		}
		$this->template->title = 'People';
		$this->view->people = ORM::factory('Person')
				->order_by('name')
				->find_all();
	}

	public function action_edit()
	{
		if ($this->user->auth_level_id < 10)
		{
			$this->controller_view->content = NULL;
			$this->add_flash_message('Access Denied');
			$this->redirect(URL::site('/'));
		}
		$id = $this->request->param('id');
		$person = ORM::factory('Person', $id);
		if (!is_null($id) AND !$person->loaded())
		{
			$msg = "No record found with an ID of '$id'. "
					.HTML::anchor('people', 'Browse all people.');
			$this->add_template_message($msg);
			$this->controller_view->content = NULL;
		}
		$this->view->person = $person;
		$this->template->title = ($person->loaded()) ? 'Editing '.$person->name : 'New Person';
		if (Arr::get($_POST, 'save'))
		{
			$person->values($_POST);
			$person->save();
			$this->add_flash_message('Record Saved', 'success');
			$this->request->redirect('people');
		}
	}

	public function action_login()
	{
		$this->template->selected_toplink = Route::url('login');
		try
		{
			if (!isset($_GET['openid_mode']))
			{
				if (isset($_POST['openid_identifier']))
				{
					$openid = new LightOpenID;
					$openid->identity = $_POST['openid_identifier'];
					$this->redirect($openid->authUrl());
				}
			}
			elseif ($_GET['openid_mode'] == 'cancel')
			{
				$this->add_template_message('You canceled authentication! (Why?)', 'success');
			}
			else
			{
				$openid = new LightOpenID;
				if ($openid->validate())
				{
					$this->user->where('openid_identity', '=', $openid->identity)->find();
					if ($this->user->loaded())
					{ // This user has been found
						$this->add_flash_message('Login Successful', 'success');
					}
					else // Create a new user
					{
						$attrs = $openid->getAttributes();
						$this->user->openid_identity = $openid->identity;
						$this->user->name = Arr::get($attrs, 'namePerson', Arr::get($attrs, 'namePerson/friendly', $openid->identity));
						$this->add_flash_message('Welcome!', 'success');
						// If this is the only user, make them a superuser
						if (ORM::factory('Person')->count_all() == 0)
						{
							$this->user->auth_level_id = 10;
							$msg = 'As the first ever user, your account has '
									.'been given the highest privileges.';
							$this->add_flash_message($msg, 'success');
						}
						$this->user->save();
					}
					$this->session->set('user', $this->user);
					$this->redirect('/');
				}
			}
		}
		catch (ErrorException $e)
		{
			$this->add_template_message($e->getMessage(), 'error');
		}
		$this->template->title = 'Please Log In';
		$this->template->jquery = TRUE;
	}

	public function action_logout()
	{
		$this->session->delete('user')->destroy();
		$this->add_flash_message('You are now logged out.', 'success');
		$this->request->redirect('');
	}

}