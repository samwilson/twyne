<?php

namespace Samwilson\Twyne;

use cebe\markdown\Markdown;
use Exception;
use Krinkle\Intuition\Intuition;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * A Twig template.
 * @property string $title Page title.
 * @property Exception $exception Any Exception.
 */
class Template
{

    /** @var array */
    private $data = array();

    /** @var string */
    private $template = false;

    /** @var Environment */
    private $twig;

    /** @var Intuition */
    private $intuition;

    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    public function __construct($template)
    {
        $this->template = $template;
        $config = new Config();
        $this->data['app_title'] = App::name();
        $this->data['app_version'] = App::version();
        $this->data['mode'] = $config->mode();
        $this->data['baseurl'] = $config->baseUrl();
        $this->data['baseurl_full'] = $config->baseUrl(true);
        $this->data['app_dir'] = $config->appDir();
        $this->data['site_title'] = $config->siteTitle();
        $this->data['stylesheet'] = 'base';

        // Load template directories.
        $loader = new FilesystemLoader();
        $loader->addPath($this->app_dir . '/tpl');

        // Set up Twig.
        $this->debug = $this->mode === Config::MODE_DEBUG;
        $twig = new Environment($loader, [
            'debug' => $this->debug,
            'strict_variables' => $this->debug,
        ]);
        $this->twig = $twig;
        if ($this->debug) {
            $twig->addExtension(new DebugExtension());
        }

        // Intuition i18n support.
        $intuition = new Intuition('twyne');
        $this->intuition = $intuition;
        $intuition->registerDomain('twyne', $this->data['app_dir'] . '/i18n');
        $twig->addFunction(new TwigFunction('msg', [$this, 'msg']));
        $twig->addFunction(new TwigFunction('date_create', 'date_create'));
        $twig->addFilter(new TwigFilter('md2html', function ($in) {
            $md = new Markdown();
            $md->html5 = true;
            return $md->parse($in);
        }));
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return (isset($this->data[$name])) ? $this->data[$name] : null;
    }

    /**
     * @param string $key
     * @param string[]|string|null $params
     * @return string
     */
    public function msg(string $key, $params = []): string
    {
        if (!is_array($params)) {
            $params = [$params];
        }
        return $this->intuition->msg($key, [
            'domain' => 'twyne',
            'variables' => $params,
        ]);
    }

    public function render(?bool $echo = false)
    {
        $string = $this->twig->render($this->template . '.twig', $this->data);
        if ($echo) {
            echo $string;
            exit(0);
        } else {
            return $string;
        }
    }
}
