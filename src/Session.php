<?php

namespace Samwilson\Twyne;

class Session
{

    public function __construct()
    {
        $active = session_status() === PHP_SESSION_ACTIVE;
        if ($active) {
            return;
        }
        $lifetime = 24 * 60 * 60;
        ini_set('session.gc_maxlifetime', $lifetime);
        ini_set('session.cookie_lifetime', $lifetime);
        $config = new Config();
        session_set_cookie_params($lifetime, $config->baseUrl());
        session_name(App::name());
        session_start();
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public function get($key, $default = null)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public function getAll()
    {
        return $_SESSION;
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function getAndDelete($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    public function regenerate()
    {
        $active = session_status() === PHP_SESSION_ACTIVE;
        if (!$active) {
            return;
        }
        session_regenerate_id(true);
    }

    public function destroy()
    {
        $active = session_status() === PHP_SESSION_ACTIVE;
        if (!$active) {
            return;
        }
        session_unset();
        session_destroy();
        session_write_close();
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - ( 60 * 60 ),
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
}
