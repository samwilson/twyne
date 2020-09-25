<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Setting;
use App\Repository\SettingRepository;
use App\Settings;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(Request $request, SettingRepository $settingRepository, EntityManagerInterface $em)
    {
        if ($request->isMethod('POST')) {
            foreach ($request->get('settings') as $name => $value) {
                $setting = $settingRepository->findOneBy(['name' => $name]);
                if (!$setting) {
                    $setting = new Setting();
                }
                $setting->setName($name);
                $setting->setValue($value);
                $em->persist($setting);
            }
            $em->flush();
            $this->addFlash('success', 'Settings saved.');
            return $this->redirectToRoute('settings');
        }
        return $this->render('setting/index.html.twig', [
            'controller_name' => 'SettingController',
        ]);
    }
}
