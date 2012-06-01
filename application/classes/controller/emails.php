<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Emails extends Controller_Base {

	public function action_inbox()
	{
		if ($this->user->auth_level_id < 10)
		{
			$this->add_flash_message('Access Denied');
			$this->request->redirect('/');
		}
		global $email_config;
		$this->selected_toplink = '/emails/inbox';
		$this->template->title = 'Inbox';
		require_once 'Net/IMAP.php';
		$imap = new Net_IMAP($email_config['server'], $email_config['port']);
		$login = $imap->login($email_config['username'], $email_config['password'], true, false);
		if (PEAR::isError($login))
		{
			$this->add_template_message('Unable to log in to mail server: '.$login->getMessage(), 'error');
			return;
		}
		$mbox_select = $imap->selectMailbox($email_config['inbox']);
		if (PEAR::isError($mbox_select))
		{
			$this->add_template_message("Unable to select mailbox '".$mbox_select->getMessage()."'.", 'error');
		}

		// Save email
		if (isset($_POST['save']))
		{
			$email = ORM::factory('emails');
			$email->date_and_time = $_POST['date_and_time'];
			$email->to_id = $_POST['to_id'];
			$email->from_id = $_POST['from_id'];
			$email->subject = $_POST['subject'];
			$email->message_body = $_POST['message_body'];
			$email->save();
		}
		if ((isset($_POST['save']) && $_POST['save'] == 'Archive + Delete') || isset($_POST['delete']))
		{
			$imap->deleteMsg(1);
			//$imap->expunge();
			$imap->disconnect(TRUE);
			$this->request->redirect('emails/inbox');
		}
		elseif (isset($_POST['save']) && $_POST['save'] == 'Archive Only')
		{
			$imap->disconnect();
			$this->request->redirect('emails/inbox');
		}

		// Get message count
		$msg_count = $imap->numMsg();
		if (PEAR::isError($msg_count))
		{
			$this->add_template_message('No messages found: '.$msg_count->getMessage(), 'error');
			return;
		}
		else
		{
			$this->add_template_message($msg_count.' messages remain to be processed.');
			if ($msg_count == 0)
			{
				return;
			}
		}

		// Get people for select elements
		$this->view->people = array(NULL=>'Not in DB');
		foreach (ORM::factory('people')->order_by('name')->find_all() as $person)
		{
			$this->view->people[$person->id] = $person->name.' <'.$person->email_address.'>';
		}

		// Get message headers.
		$headers = $imap->getSummary(1);
		if (PEAR::isError($headers))
		{
			$this->add_template_message('Failed to parse headers of message: '.$headers->message, 'error');
			return;
		}
		$headers = $headers[0];
		$this->view->headers = $headers;

		// Set defaults.
		$from_id = NULL;
		$to_id = NULL;
		$subject = $headers['SUBJECT'];
		if (empty($subject) || $subject == 'NIL')
		{
			$subject = '[No Subject]';
		}
		elseif (mb_check_encoding($subject))
		{
			$subject = mb_decode_mimeheader($subject);
		}
		$this->view->email = new stdClass();
		$this->view->email->date_and_time = date('Y-m-d H:i:s', strtotime($headers['DATE']));
		$this->view->email->subject = $subject;
		//$this->view->email->to_id = $to_id;
		//$this->view->email->from_id = $from_id;
		// Determine correspondents
		$email_address_pattern = "/[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\.\-_]*/i";
		preg_match($email_address_pattern, $headers['FROM'][0]['EMAIL'], $from_email_address);
		preg_match($email_address_pattern, $headers['TO'][0]['EMAIL'], $to_email_address);
		if (isset($from_email_address[0]))
		{
			$this->view->email->from = ORM::factory('people')
					->where('email_address', 'LIKE', '%'.$headers['FROM'][0]['EMAIL'].'%')
					->or_where('notes', 'LIKE', '%'.$headers['FROM'][0]['EMAIL'].'%')
					->find();
		}
		if (isset($to_email_address[0]))
		{
			$this->view->email->to = ORM::factory('people')
					->where('email_address', 'LIKE', '%'.$headers['TO'][0]['EMAIL'].'%')
					->or_where('notes', 'LIKE', '%'.$headers['TO'][0]['EMAIL'].'%')
					->find();
		}

		// Set message body
		$this->view->email->message_body = $this->get_message_body($imap, $imap->getStructure(1));
	}

	/**
	 * Get message body.  Recurses.
	 *
	 * @param Net_IMAP $imap
	 * @return string
	 */
	private function get_message_body($imap, $structure)
	{
		$message_body = "";
		if (isset($structure->subParts))
		{
			foreach ($structure->subParts as $pid=>$part)
			{
				if ($part->type == 'TEXT' && $part->subType == 'PLAIN')
				{
					if ($part->encoding == 'QUOTED-PRINTABLE')
					{
						$message_body .= quoted_printable_decode($imap->getBodyPart(1, $part->partID));
					}
					elseif ($part->encoding == 'BASE64')
					{
						$message_body .= base64_decode($imap->getBodyPart(1, $part->partID));
					}
					else
					{
						$message_body .= $imap->getBodyPart(1, $part->partID);
					}
				}
				elseif (isset($part->subParts))
				{
					$message_body .= $this->get_message_body($imap, $part);
				}
			}
		}
		else
		{ // If no parts, then must be plain.
			if ($structure->type == 'TEXT' && $structure->subType == 'HTML')
			{
				$message_body = ($structure->encoding == 'BASE64') ? base64_decode($imap->getBody(1)) : $imap->getBody(1);
				$message_body = quoted_printable_decode($message_body);
				$message_body = strip_tags($message_body);
				$message_body = str_replace("&nbsp;", " ", $message_body);
				$message_body = html_entity_decode($message_body, ENT_QUOTES, $structure->parameters['CHARSET']);
			}
			else
			{
				if ($structure->encoding == 'BASE64')
				{
					$message_body = base64_decode($imap->getBody(1));
				}
				else
				{
					$message_body = utf8_encode(quoted_printable_decode($imap->getBody(1)));
				}
			}
		}
		return $message_body;
	}

	public function action_index()
	{
		$this->selected_toplink = '/emails';
		// Check permission
		if ($this->user->auth_level_id < 10)
		{
			$this->add_flash_message('Access Denied');
			$this->request->redirect('/');
		}
		$this->template->title = 'Emails';

		// Get emails and people info
		$this->view->year = Arr::get($_GET, 'year', FALSE);
		$this->view->with = ORM::factory('people', Arr::get($_GET, 'with', NULL));
		$this->view->emails = ORM::factory('emails')
				->where(DB::expr('YEAR(date_and_time)'), '=', $this->view->year)
				->and_where_open()
				->where('to_id', '=', $this->view->with)->or_where('from_id', '=', $this->view->with)
				->and_where_close()
				->order_by('date_and_time')
				->find_all();
		if (count($this->view->emails) > 0)
		{
			$this->template->title = 'Emails with '.$this->view->with->name.' in '.$this->view->year;
		}
		$this->view->people = ORM::factory('people')
				->where('id', '!=', $this->user->id)
				->order_by('name')
				->find_all();

		// Save and send.
		if (isset($_POST['send']))
		{
			$new_email = ORM::factory('emails');
			$new_email->from_id = $this->user->id;
			$new_email->to_id = $_POST['to_id'];
			$new_email->subject = $_POST['subject'];
			$new_email->message_body = $_POST['message_body'];
			$new_email->date_and_time = date('Y-m-d H:i:s');
			$body = $_POST['message_body'];
			if (!empty($_POST['last_body']))
			{
				$body .= "\n\n
------------------------------------------------------------------------------
Date: ".$_POST['last_date_and_time']." (".date_default_timezone_get().")
From: ".$this->user->name." <".$this->user->email_address.">
  To: ".$this->view->with->name." <".$this->view->with->email_address.">
------------------------------------------------------------------------------

".wordwrap($_POST['last_body'], 78)."

------------------------------------------------------------------------------
";
			}
			$headers = "From: ".$this->user->name." <".$this->user->email_address.">\r\n";
			if (!mail($_POST['to'], $_POST['subject'], $body, $headers))
			{
				die("Something bad happened when sending email to: ".$_POST['to']);
			}
			$new_email->save();
			$this->request->redirect('emails/index?with='.$this->view->with.'&year='.$this->view->year.'#reply-form');
		} // end send
	}

	public function action_latex($year = NULL)
	{
		if ($this->user->auth_level < 10 && !Kohana::$is_cli)
		{
			$this->add_flash_message('Access Denied');
			$this->request->redirect('/');
		}

		if (!is_numeric($year) || $year < 1111 || $year > 9999)
		{
			$this->add_flash_message('Please specify a year for which to export an album.');
			$this->request->redirect('emails');
		}

		$people = ORM::factory('People')->order_by('name')->find_all()->as_array('id');
		$emails = array();
		foreach ($people as $person)
		{
			$emails[$person->id] = ORM::factory('Emails')
					->where(DB::expr('YEAR(date_and_time)'), '=', $year)
					->and_where_open()
					->where('to_id', '=', $person->id)
					->or_where('from_id', '=', $person->id)
					->and_where_close()
					->order_by('date_and_time')
					->find_all();
		}
		$latex = View::factory('emails/latex')
				->bind('people', $people)
				->bind('emails', $emails)
				->bind('year', $year)
				->render();
		$filename = DATAPATH.'/emails/'.$year.'.tex';
		file_put_contents($filename, $latex);

		if (!Kohana::$is_cli)
		{
			$this->add_flash_message('The <span class="latex">L<sup>a</sup>&Tau;<sub>&epsilon;</sub>&Chi;</span> file for all '.$year.' emails has been written to disk.', 'success');
			$this->request->redirect('emails');
		}
		else
		{
			echo "The LaTeX file for all $year emails has been written to\n"
			.$filename;
			exit();
		}
	}

}

