<?php

namespace Samwilson\Twyne\Controller;

use Exception;
use Samwilson\Twyne\App;
use Samwilson\Twyne\Data\User;

class UserController extends ControllerBase
{

    public function loginGet(): void
    {
        $tpl = $this->getTemplate('login.html');
        $tpl->ident = $this->session->getAndDelete('ident');
        $this->outputTemplate($tpl);
    }

    public function loginPost()
    {
        foreach (['register', 'reset'] as $redirectableAction) {
            if ($this->getParamPost($redirectableAction)) {
                $this->session->set('username', $this->getParamPost('ident'));
                $this->redirect('/' . $redirectableAction);
            }
        }
        $ident = $this->getParamPost('ident');
        $user = User::loadByEmailOrUserame($ident, $ident);
        if (!$user->exists()) {
            $this->addAlert('warning', 'login-failed');
            $this->session->set('ident', $this->getParamPost('ident'));
            $this->redirect('/login');
        }
        $loggedIn = $user->checkPassword($this->getParamPost('password'));
        if (!$loggedIn) {
            $this->addAlert('warning', 'login-failed');
            $this->session->set('ident', $this->getParamPost('ident'));
            $this->redirect('/login');
        }
        $this->session->regenerate();
        $this->session->set('user_id', $user->getId());
        $this->addAlert('success', 'login-successful');
        $this->redirect('/');
    }

    public function registerGet()
    {
        $tpl = $this->getTemplate('register.html');
        $tpl->username = $this->session->getAndDelete('username');
        $tpl->email = $this->session->getAndDelete('email');
        $this->outputTemplate($tpl);
    }

    public function registerPost()
    {
        $username = $this->getParamPost('username');
        $pass = $this->getParamPost('password');
        $passValidation = $this->getParamPost('password_validation');
        if ($pass !== $passValidation) {
            $this->addAlert('warning', 'passwords-no-match');
            $this->session->set('username', $this->getParamPost('username'));
            $this->session->set('email', $this->getParamPost('email'));
            $this->redirect('/register');
        }

        $user = User::register($username, $this->getParamPost('email'), $pass);
        if ($user instanceof User) {
            $this->session->set('ident', $this->getParamPost('email'));
            $tpl = $this->getTemplate('register_email.txt');
            $tpl->username = $user->getUsername();
            App::sendEmail($user->getEmail(), $tpl->msg('register-email-subject'), $tpl->render());
        } else {
            $this->session->set('ident', $username);
        }
        $this->addAlert('success', 'check-your-email');
        $this->redirect('/login');
    }

    public function resetPartOneGet()
    {
        $tpl = $this->getTemplate('reset.html');
        $tpl->username = $this->session->getAndDelete('username');
        $tpl->email = $this->session->getAndDelete('email');
        $this->outputTemplate($tpl);
    }

    public function resetPartOnePost()
    {
        $user = User::loadByEmailOrUserame($this->getParamPost('email'), $this->getParamPost('username'));
        if ($user->exists()) {
            $tpl = $this->getTemplate('reset_email.txt');
            $tpl->reset_token = $user->getReminderToken();
            $tpl->username = $user->getUsername();
            App::sendEmail($user->getEmail(), $tpl->msg('reset-email-subject'), $tpl->render());
            $this->session->set('reset_user', $user->getId());
        } else {
            // Pretend we're sending an email.
            sleep(rand(2, 6));
        }
        $this->addAlert('success', 'check-your-email');
        $this->redirect('/login');
    }

    public function resetPartTwoGet(array $args)
    {
        if (!isset($args['token'])) {
            return;
        }
        $userFromSession = $this->session->getAndDelete('reset_user');
        if (!$userFromSession) {
            $this->addAlert('warning', 'reset-try-again');
            $this->redirect('/reset');
        }
        $user = User::loadById($userFromSession);
        try {
            $user->checkReminderToken($args['token']);
        } catch (Exception $exception) {
            $this->addAlert('warning', $exception->getMessage());
            $this->redirect('/reset');
        }
        $this->session->set('user_id', $user->getId());
        $this->addAlert('success', 'reset-successful');
        $this->redirect('/');
    }

    public function logoutGet()
    {
        $tpl = $this->getTemplate('logout.html');
        $this->outputTemplate($tpl);
    }

    public function logoutPost()
    {
        $this->session->destroy();
        $this->redirect('/');
    }
}
