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

	/** @var boolean Whether to include the JQuery library or not. */
	public $jquery = FALSE;

	public function before()
	{
		parent::before();

		/*
		 * View.  There is a hierarchy from template, to controller_view, to $this->view.
		 */
		if (Kohana::find_file('views/'.$this->request->controller(), $this->request->action()))
		{
			$this->view = View::factory($this->request->controller().'/'.$this->request->action());
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
		if (empty($this->user))
		{
			$this->user = ORM::factory('People');
			$this->user->auth_level = ORM::factory('AuthLevels', 0);
		}
		$this->template->bind_global('user', $this->user);


		/*
		 * Add flash messages to the template, then clear them from the session.
		 */
		foreach (Session::instance()->get('flash_messages', array()) as $msg)
		{
			$this->add_template_message($msg['message'], $msg['status']);
		}
		Session::instance()->set('flash_messages', array());

		/*
		 * Top links.  'url' should start with a slash.
		 */
		$toplinks = array(
			array('url'=>'/blog', 'title'=>'Blog'),
			array('url'=>'/images', 'title'=>'Images'),
			array('url'=>'/journal', 'title'=>'Journal'),
		);
		if ($this->user->auth_level_id >= 10)
		{
			$toplinks = array_merge($toplinks, array(
				array('url'=>'/journal/edit', 'title'=>'New Journal Entry'),
				array('url'=>'/emails', 'title'=>'Emails'),
				array('url'=>'/emails/inbox', 'title'=>'Inbox'),
				array('url'=>'/people', 'title'=>'People'),
			));
		}
		if ($this->user->loaded())
			$toplinks[] = array('url'=>'logout', 'title'=>'Log Out');
		else
			$toplinks[] = array('url'=>'login', 'title'=>'Log In');
		$this->template->bind_global('toplinks', $toplinks);
		/** @var string Starts with a slash. */
		$this->selected_toplink = '/'.$this->request->controller();
		$this->template->bind_global('selected_toplink', $this->selected_toplink);
		$this->template->bind_global('jquery', $this->jquery);
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
