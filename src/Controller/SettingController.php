<?php

namespace App\Controller;

use App\Settings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class SettingController extends AbstractController
{
    /**
     * @Route("/settings", name="settings")
     * @isGranted("ROLE_ADMIN")
     */
    public function index(Request $request, Settings $settings)
    {
        if ($request->isMethod('POST')) {
            $settings->saveData($request->get('settings', []));
            $this->addFlash('success', 'Settings saved.');
            return $this->redirectToRoute('settings');
        }
        return $this->render('setting/index.html.twig', [
            'controller_name' => 'SettingController',
        ]);
    }
}
