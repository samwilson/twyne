<?php

namespace App\Controller;

use App\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends ControllerBase
{

    /**
     * @Route("/settings", name="settings_save", methods={"POST"})
     * @isGranted("ROLE_ADMIN")
     */
    public function save(Request $request, Settings $settings)
    {
        $submittedToken = $request->request->get('token');
        if ($request->isMethod('post') && $this->isCsrfTokenValid('settings-save', $submittedToken)) {
            $settings->saveData($request->get('settings', []));
            $this->addFlash(self::FLASH_SUCCESS, 'Settings saved.');
        }
        return $this->redirectToRoute($request->get('returnroute', 'settings'));
    }

    /**
     * @Route("/settings", name="settings")
     * @isGranted("ROLE_ADMIN")
     */
    public function index()
    {
        return $this->render('setting/index.html.twig', [
            'controller_name' => 'SettingController',
        ]);
    }

    /**
     * @Route("/settings/css", name="settings_css")
     * @Route("/settings/js", name="settings_js")
     * @isGranted("ROLE_ADMIN")
     */
    public function frontend(Request $request, Settings $settings)
    {
        $settingName = 'site_css';
        $settingValue = $settings->getSiteCss();
        $codeMirrorMode = 'css';
        $settingLabel = 'settings.styles_desc';
        if ($request->attributes->get('_route') === 'settings_js') {
            $settingName = 'site_js';
            $settingValue = $settings->getSiteJs();
            $codeMirrorMode = 'javascript';
            $settingLabel = 'settings.scripts_desc';
        }
        return $this->render('setting/frontend.html.twig', [
            'controller_name' => 'SettingController',
            'setting_name' => $settingName,
            'setting_value' => $settingValue,
            'codemirror_mode' => $codeMirrorMode,
            'setting_label' => $settingLabel,
            'no_frontend' => true,
        ]);
    }

    /**
     * @Route("/site.{ext}", name="frontend_file", requirements={"ext"="(css|js)"})
     */
    public function frontendFile($ext, Settings $settings)
    {
        $response = new Response();
        if ($ext === 'css') {
            $response->setContent($settings->getSiteCss());
            $response->headers->set('Content-Type', 'text/css');
        } else {
            $response->setContent($settings->getSiteJs());
            $response->headers->set('Content-Type', 'text/javascript');
        }
        return $response;
    }
}
