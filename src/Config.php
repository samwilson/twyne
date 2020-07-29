<?php

namespace Samwilson\Twyne;

use Exception;

class Config
{

    /** @var string */
    protected $configFile;

    /** @var mixed */
    protected static $configVars;

    public const MODE_PROD = 'production';

    public const MODE_DEBUG = 'debug';

    /**
     * @param string|null $configFile Path and filename of the config file to use.
     * @throws Exception If the config file is not readable.
     */
    public function __construct($configFile = null)
    {
        if ($configFile === null) {
            if (substr(basename($_SERVER['PHP_SELF']), 0, 7) === 'phpunit') {
                $configFile = $this->appDir() . '/tests/config.php';
            } else {
                $configFile = $this->appDir() . '/config.php';
            }
        }
        if (!is_readable($configFile)) {
            throw new Exception("Config file not readable: $configFile");
        }
        $this->configFile = $configFile;
    }

    protected function get($name, $default = null)
    {
        if (!isset(self::$configVars[$this->configFile])) {
            require_once $this->configFile;
            self::$configVars[$this->configFile] = get_defined_vars();
            unset(self::$configVars[$this->configFile]['name'], self::$configVars[$this->configFile]['default']);
        }
        if (isset(self::$configVars[$this->configFile][$name])) {
            return self::$configVars[$this->configFile][$name];
        }
        return $default;
    }

    public function debug(): bool
    {
        return $this->mode() === static::MODE_DEBUG;
    }

    /**
     * @return string One of the Config::MODE_* constants.
     */
    public function mode(): string
    {
        return $this->get('mode', self::MODE_PROD);
    }

    public function smtpHost(): string
    {
        return $this->get('smtpHost');
    }

    public function smtpPort(): int
    {
        return (int)$this->get('smtpPort', 25);
    }

    public function smtpAuth(): bool
    {
        return (bool)$this->get('smtpAuth', true);
    }

    public function smtpUsername(): string
    {
        return $this->get('smtpUsername', '');
    }

    public function smtpPassword(): string
    {
        return $this->get('smtpPassword', '');
    }

    public function filesystems()
    {
        $default = [
            'cache' => [
                'type' => 'local',
                'root' => __DIR__ . '/../data/cache',
            ],
            'storage' => [
                'type' => 'local',
                'root' => __DIR__ . '/../data/storage',
            ],
        ];
        return $this->get('filesystems', $default);
    }

    /**
     * Get the base filesystem path to the app's installation directory. Never has a trailing slash.
     * @return string
     */
    public function appDir()
    {
        return rtrim(dirname(__DIR__), '/');
    }

    /**
     * Get the Base URL of the application. Never has a trailing slash.
     * @return string
     */
    public function baseUrl($absolute = false)
    {
        $calculatedBaseUrl = substr($_SERVER['SCRIPT_NAME'], 0, -(strlen('index.php')));
        $baseUrl = $this->get('base_url', $calculatedBaseUrl);
        $baseUrlTrimmed = rtrim($baseUrl, ' /');
        $protocol = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';
        $host = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'localhost';
        return $absolute ? $protocol . $host . $baseUrlTrimmed : $baseUrlTrimmed;
    }

    public function siteTitle()
    {
        return $this->get('siteTitle', 'A ' . App::name() . ' website');
    }

    public function siteEmail()
    {
        return $this->get('site_email', 'admin@example.org');
    }

    public function databaseHost()
    {
        return self::get('databaseHost', 'localhost');
    }

    public function databaseName()
    {
        return self::get('databaseName', 'twyne');
    }

    public function databaseUser()
    {
        return self::get('databaseUser', 'twyne');
    }

    public function databasePassword()
    {
        return self::get('databasePassword', '');
    }

    /**
     * Get temp directory on the local filesystem.
     * @return string Always has a trailing slash.
     */
    public function tmpDir()
    {
        $tmpDir = self::get('tmpDir', sys_get_temp_dir());
        $realTmpDir = realpath($tmpDir);
        if ($realTmpDir === false || !is_dir($realTmpDir)) {
            throw new Exception("tmpDir is not a directory: $tmpDir");
        }
        return rtrim($realTmpDir, '/') . '/';
    }
}
