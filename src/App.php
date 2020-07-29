<?php

namespace Samwilson\Twyne;

use Error;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class App
{

    public static function name()
    {
        return 'Twyne';
    }

    /**
     * Get the application's version.
     *
     * Conforms to Semantic Versioning guidelines.
     * @link http://semver.org
     * @return string
     */
    public static function version()
    {
        return '0.1.0';
    }

    /**
     * Turn a spaced or underscored string to camelcase (with no spaces or underscores).
     *
     * @param string $str
     * @return string
     */
    public static function camelcase(string $str): string
    {
        $separators = ['_', '-'];
        return str_replace(' ', '', ucwords(str_replace($separators, ' ', strtolower($str))));
    }

    /**
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    public static function snakeCase(string $str, string $delimiter = '_'): string
    {
        $lcfirst = lcfirst($str);
        $lower = strtolower($lcfirst);
        $result = '';
        for ($i = 0; $i < strlen($lcfirst); $i++) {
            $result .= ($lcfirst[$i] === $lower[$i] ? '' : $delimiter) . $lower[$i];
        }
        return $result;
    }

    /**
     * Get the filesystem manager.
     *
     * @return MountManager
     * @throws Exception
     */
    public static function getFilesystem()
    {
        $config = new Config();
        $manager = new MountManager();
        foreach ($config->filesystems() as $name => $fsConfig) {
            $adapterName = '\\League\\Flysystem\\Adapter\\' . self::camelcase($fsConfig['type']);
            $adapter = new $adapterName($fsConfig['root']);
            $fs = new Filesystem($adapter);
            $manager->mountFilesystem($name, $fs);
        }
        return $manager;
    }

    /**
     * Show an exception/error with the error template.
     * @param Exception|Error $exception
     */
    public static function exceptionHandler($exception)
    {
        $template = new Template('error.twig');
        $template->title = 'Error';
        $template->exception = $exception;
        $template->render(true);
    }

    /**
     * Delete a directory and its contents.
     * @link http://stackoverflow.com/a/8688278/99667
     * @param string $path
     * @return bool
     */
    public static function deleteDir(string $path)
    {
        if (empty($path)) {
            return false;
        }
        return is_file($path)
            ? @unlink($path)
            : array_map([__CLASS__, __FUNCTION__], glob($path . '/*')) == @rmdir($path);
    }

    public static function sendEmail($to, $subject, $body)
    {
        $mail = new PHPMailer(true);
        $config = new Config();

        $mail->isSMTP();
        $mail->SMTPDebug = $config->debug() ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->Host = $config->smtpHost();
        $mail->Port = $config->smtpPort();
        $mail->SMTPAuth = $config->smtpAuth();
        $mail->Username = $config->smtpUsername();
        $mail->Password = $config->smtpPassword();

        $mail->setFrom($config->siteEmail(), $config->siteTitle());
        $mail->addAddress($to);
        $mail->Subject = '[' . $config->siteTitle() . '] ' . $subject;
        $mail->Body = $body;

        $mail->send();
    }
}
