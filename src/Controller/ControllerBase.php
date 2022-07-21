<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class ControllerBase extends AbstractController
{
    /** @var string */
    protected const FLASH_ERROR = 'error';

    /** @var string */
    protected const FLASH_NOTICE = 'notice';

    /** @var string */
    protected const FLASH_SUCCESS = 'success';

    /** @var bool */
    private $requireTwoFactorAuth;

    /**
     * @return User|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }

    public function __construct($requireTwoFactorAuth)
    {
        $this->requireTwoFactorAuth = $requireTwoFactorAuth;
    }

    /**
     * Get authorization response (e.g. 403, redirect, etc.).
     * This is used in \App\EventListener\ControllerListener (which is why it's public).
     */
    public function getAuthResponse(): ?Response
    {
        if (!$this->requireTwoFactorAuth || !$this->getUser() || $this->getUser()->getTwoFASecret()) {
            return null;
        }
        $this->addFlash(self::FLASH_NOTICE, 'Please set up 2FA.');
        return $this->redirectToRoute('2fa_get');
    }
}
