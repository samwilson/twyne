<?php

namespace App\Controller;

use App\Repository\UserGroupRepository;
use App\Settings;
use OAuth\Common\Storage\Session;
use Samwilson\PhpFlickr\PhpFlickr;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    public function index(UserGroupRepository $userGroupRepository)
    {
        return $this->render('setting/index.html.twig', [
            'controller_name' => 'SettingController',
            'user_groups' => $userGroupRepository->findAll(),
        ]);
    }

    /**
     * @Route("/settings/flickr/connect", name="flickr_connect")
     * @isGranted("ROLE_ADMIN")
     */
    public function flickrConnect(Settings $settings): Response
    {
        $flickr = new PhpFlickr($settings->flickrApiKey(), $settings->flickrApiSecret());
        $flickr->setOauthStorage(new Session());
        $callbackUrl = $this->generateUrl('flickr_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        return $this->redirect($flickr->getAuthUrl('write', $callbackUrl));
    }

    /**
     * @Route("/settings/flickr/callback", name="flickr_callback")
     * @isGranted("ROLE_ADMIN")
     */
    public function flickrCallback(Settings $settings, Request $request): Response
    {
        if (!$request->get('oauth_verifier') || !$request->get('oauth_token')) {
            $this->addFlash(self::FLASH_NOTICE, 'No OAuth verifier params passed for Flickr callback.');
            return $this->redirectToRoute('settings');
        }
        $flickr = new PhpFlickr($settings->flickrApiKey(), $settings->flickrApiSecret());
        $flickr->setOauthStorage(new Session());
        $accessToken = $flickr->retrieveAccessToken($request->get('oauth_verifier'), $request->get('oauth_token'));
        $settings->saveData([
            'flickr_token' => $accessToken->getAccessToken(),
            'flickr_token_secret' => $accessToken->getAccessTokenSecret(),
        ]);
        return $this->redirectToRoute('settings');
    }

    /**
     * @Route("/settings/flickr/disconnect", name="flickr_disconnect")
     * @isGranted("ROLE_ADMIN")
     */
    public function flickrDisconnect(Settings $settings): Response
    {
        $settings->saveData([
            'flickr_token' => '',
            'flickr_token_secret' => '',
        ]);
        return $this->redirectToRoute('settings');
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
