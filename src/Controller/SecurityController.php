<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\UserGroupRepository;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends ControllerBase
{

    /**
     * @Route("/register", name="register")
     */
    public function register(
        Request $request,
        Settings $settings,
        UserPasswordEncoderInterface $passwordEncoder,
        GuardAuthenticatorHandler $guardHandler,
        LoginFormAuthenticator $formAuthenticator,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository
    ): Response {
        if (!$settings->userRegistrations()) {
            throw $this->createAccessDeniedException('User registration is disabled on this site.');
        }
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->get('email'));
            $user->setUsername($request->get('username'));
            $user->setPassword($passwordEncoder->encodePassword($user, $request->get('password')));
            if ($userRepository->count([]) === 0) {
                // The first registered user is made an admin.
                $user->setRoles(['ROLE_ADMIN']);
            }
            $user->addGroup($userGroupRepository->find(UserGroup::PUBLIC));
            $contact = new Contact();
            $contact->setName('User ' . $user->getUsername());
            $user->setContact($contact);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->persist($contact);
            $em->flush();
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $formAuthenticator,
                'main'
            );
        } else {
            return $this->render('security/register.html.twig', ['error' => '']);
        }
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Settings $settings): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'user_registrations' => $settings->userRegistrations(),
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('home');
    }
}
