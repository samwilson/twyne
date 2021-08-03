<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class ControllerBase extends AbstractController
{

    /** @var string */
    protected const FLASH_ERROR = 'error';

    /** @var string */
    protected const FLASH_NOTICE = 'notice';

    /** @var string */
    protected const FLASH_SUCCESS = 'success';

    /**
     * @return User|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }
}
