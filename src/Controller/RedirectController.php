<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Redirect;
use App\Entity\UserGroup;
use App\Repository\ContactRepository;
use App\Repository\RedirectRepository;
use App\Repository\UserGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RedirectController extends ControllerBase
{
    /**
     * @Route("/redirects", name="redirects")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(RedirectRepository $redirectRepository)
    {
        $redirects = $redirectRepository->findAll();
        return $this->render('setting/redirects.html.twig', [
            'redirects' => $redirects,
            'statuses' => array_flip($redirectRepository->getStatuses()),
        ]);
    }

    /**
     * @Route("/redirect", name="redirect_new")
     * @Route("/redirect/{id}",
     *     name="redirect_edit",
     *     requirements={"id"="\d+"}
     * )
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Request $request, RedirectRepository $redirectRepository)
    {
        if ($request->get('id')) {
            $redirect = $redirectRepository->find($request->get('id'));
        } else {
            $redirect = new Redirect();
            $redirect->setStatus(302);
        }
        return $this->render('setting/redirect.html.twig', [
            'statuses' => $redirectRepository->getStatuses(),
            'redirect' => $redirect,
        ]);
    }

    /**
     * @Route("/redirect/save", name="redirect_save")
     * @IsGranted("ROLE_ADMIN")
     */
    public function save(RedirectRepository $redirectRepository, Request $request)
    {
        $submittedToken = $request->request->get('token');
        if ($request->isMethod('post') && $this->isCsrfTokenValid('save-redirect', $submittedToken)) {
            $redirectRepository->saveFromRequest($request);
        }
        return $this->redirectToRoute('redirects');
    }
}
