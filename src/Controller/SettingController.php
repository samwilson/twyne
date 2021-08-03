<?php

namespace App\Controller;

use App\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class SettingController extends ControllerBase
{
    /**
     * @Route("/settings", name="settings")
     * @isGranted("ROLE_ADMIN")
     */
    public function index(Request $request, Settings $settings)
    {
        if ($request->isMethod('POST')) {
            $settings->saveData($request->get('settings', []));
            $this->addFlash(self::FLASH_SUCCESS, 'Settings saved.');
            return $this->redirectToRoute('settings');
        }
        return $this->render('setting/index.html.twig', [
            'controller_name' => 'SettingController',
        ]);
    }
}
