<?php

namespace Samwilson\Twyne\Controller;

use Samwilson\Twyne\App;
use Samwilson\Twyne\Config;
use Samwilson\Twyne\Database;
use Samwilson\Twyne\Data\User;
use Samwilson\Twyne\Session;
use Samwilson\Twyne\Template;

abstract class ControllerBase
{

    /** @var string[] */
    private $paramsGet;

    /** @var string[] */
    private $paramsPost;

    /** @var Session */
    protected $session;

    /** @var User */
    protected $user;

    public function __construct(array $paramsGet, array $paramsPost)
    {
        $this->paramsGet = $paramsGet;
        $this->paramsPost = $paramsPost;
        $this->session = new Session();
        if ($this->session->has('user_id')) {
            $this->user = User::loadById($this->session->get('user_id'));
        }
    }

    public function getParamGet(string $name, string $default = null)
    {
        return isset($this->paramsGet[$name]) ? $this->paramsGet[$name] : $default;
    }

    public function getParamPost(string $name, string $default = null)
    {
        return isset($this->paramsPost[$name]) ? $this->paramsPost[$name] : $default;
    }

    /**
     * Redirect to the given URL path. This method terminates execution.
     */
    public function redirect($path)
    {
        $config = new Config();
        header('Location: ' . $config->baseUrl() . '/' . ltrim($path, '/'));
        exit(0);
    }

    /**
     * @param string $name Template name, without the '.twig' extension.
     * @return Template
     */
    protected function getTemplate($name)
    {
        $tpl = new Template($name);
        $tpl->controller = App::snakeCase(basename(str_replace('\\', '/', static::class)), '-');
        $tpl->alerts = $this->session->getAndDelete('alerts');
        $tpl->user = $this->user;
        return $tpl;
    }

    protected function outputTemplate(Template $template)
    {
        $template->alerts = array_merge($template->alerts ?? [], $this->session->getAndDelete('alerts', []));
        $config = new Config();
        if ($config->mode() === Config::MODE_DEBUG) {
            $template->debug = true;
            $template->db_queries = Database::getQueries();
            $template->session_vars = $this->session->getAll();
        }
        $template->render(true);
    }

    /**
     * @param string $type One of 'error', 'warning', or 'success'.
     * @param string $message The i18n message name.
     */
    protected function addAlert($type, $message, $params = [])
    {
        $alerts = $this->session->get('alerts', []);
        $alerts[] = [
            'type' => $type,
            'message' => $message,
            'params' => $params,
        ];
        $this->session->set('alerts', $alerts);
    }
}
