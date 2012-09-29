<?php

defined('SYSPATH') OR die('No direct access allowed.');

abstract class Controller_Base extends Controller_Template {

	/** @var View The view. */
	public $view;

	/** @var Request The request that created the controller. */
	public $request;

	/** @var Session */
	public $session;

	/** @var Model_People */
	public $user;

	public function before()
	{
		parent::before();

		if (Kohana::find_file('views/', $this->request->action()))
		{
			$this->view = View::factory($this->request->action());
		}
		$this->template->bind_global('title', $this->title);
		$this->template->messages = array();
		$this->template->content = $this->view;
		$this->template->controller = $this->request->controller();
		$this->template->action = $this->request->action();
		
		/*
		 * User and Session.
		 */
		require_once(Kohana::find_file('vendor', 'openid'));
		$this->session = Session::instance();
		$this->user = $this->session->get('user');
		if (TWYNE_AUTOLOGIN) {
			$this->user = ORM::factory('People', 1);
		}
		if (empty($this->user))
		{
			$this->user = ORM::factory('People');
			$this->user->auth_level = ORM::factory('AuthLevels', 1);
		}
		$this->template->bind_global('user', $this->user);

		/*
		 * Top Links
		 */
		$this->template->toplinks = array(
			Route::url('home') => 'Home',
			Route::url('dates') => 'Dates',
			Route::url('tags') => 'Tags',
			Route::url('upload') => 'Upload',
		);
		if ($this->user->name) {
			$this->template->toplinks[Route::url('people')] = 'Your Profile';
			$this->template->toplinks[Route::url('logout')] = 'Log Out';
		} else {
			$this->template->toplinks[Route::url('login')] = 'Log In';
		}
		$this->template->selected_toplink = '';
		
		/*
		 * Add flash messages to the template, then clear them from the session.
		 */
		foreach (Session::instance()->get('flash_messages', array()) as $msg)
		{
			$this->add_template_message($msg['message'], $msg['status']);
		}
		Session::instance()->set('flash_messages', array());

	}

	protected function log($type, $message)
	{
		Kohana_Log::instance()->add($type, $message);
	}

	protected function add_template_message($message, $status = 'notice')
	{
		$this->template->messages[] = array(
			'status'=>$status,
			'message'=>$message
		);
	}

	protected function add_flash_message($message, $status = 'notice')
	{
		$flash_messages = Session::instance()->get('flash_messages', array());
		$flash_messages[] = array(
			'status'=>$status,
			'message'=>$message
		);
		Session::instance()->set('flash_messages', $flash_messages);
	}

}
