<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\UserGroupRepository;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Settings;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Otp\GoogleAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends ControllerBase
{

    /** @var string */
    private $twoFASessionKey = 'twyne-2fa-secret';

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
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {
            if ($error instanceof AuthenticationException) {
                $this->addFlash(self::FLASH_ERROR, 'Incorrect username, password, and/or 2FA key.');
            } else {
                $this->addFlash(self::FLASH_ERROR, $error->getMessage());
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'user_registrations' => $settings->userRegistrations(),
        ]);
    }

    /**
     * @Route("/2fa", name="2fa_get", methods={"GET"})
     */
    public function twoFASetup(Settings $settings, SessionInterface $session)
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $label = $settings->siteName() . ' (' . $this->getUser()->getUsername() . ')';
        $secret = GoogleAuthenticator::generateRandom();
        $session->set($this->twoFASessionKey, $secret);
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data(GoogleAuthenticator::getKeyUri('totp', $label, $secret))
            ->encoding(new Encoding('UTF-8'))
            ->size(300)
            ->build()
            ->getDataUri();
        return $this->render('security/2fa.html.twig', [
            'secret' => $secret,
            'qr_code' => $qrCode,
        ]);
    }

    /**
     * @Route("/2fa", name="2fa_post", methods={"POST"})
     */
    public function twoFAVerification(SessionInterface $session, Request $request, UserRepository $userRepository)
    {
        if ($this->getUser()->getTwoFASecret()) {
            return $this->redirectToRoute('home');
        }

        $secret = $session->get($this->twoFASessionKey);
        $session->remove($this->twoFASessionKey);
        $key = $request->get('verification');
        if (!$secret || !$key || !$this->getUser()) {
            return $this->redirectToRoute('2fa_get');
        }

        if ($userRepository->checkTwoFA($secret, $key)) {
            $this->getUser()->setTwoFASecret($secret);
            $userRepository->save($this->getUser());
            // @TODO cache used key and check before using it.
        }
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('home');
    }
}
