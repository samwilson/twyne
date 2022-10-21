<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\UserGroupRepository;
use App\Repository\UserRepository;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$settings->userRegistrations()) {
            throw $this->createAccessDeniedException('User registration is disabled on this site.');
        }
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->get('email'));
            $user->setUsername($request->get('username'));
            $user->setPassword($passwordHasher->hashPassword($user, $request->get('password')));
            if ($userRepository->count([]) === 0) {
                // The first registered user is made an admin.
                $user->setRoles(['ROLE_ADMIN']);
            }
            $user->addGroup($userGroupRepository->find(UserGroup::PUBLIC));
            $contact = new Contact();
            $contact->setName('User ' . $user->getUsername());
            $user->setContact($contact);
            $em->persist($user);
            $em->persist($contact);
            $em->flush();

            $this->addFlash(self::FLASH_SUCCESS, 'Thanks for registering, please log in.');
            return $this->redirectToRoute('login');
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
    public function twoFASetup(Settings $settings, RequestStack $requestStack)
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $otp = TOTP::create();
        $label = $settings->siteName() . ' (' . $this->getUser()->getUsername() . ')';
        $otp->setLabel($label);
        $secret = $otp->getSecret();
        $requestStack->getSession()->set($this->twoFASessionKey, $secret);
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($otp->getProvisioningUri())
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
    public function twoFAVerification(RequestStack $requestStack, UserRepository $userRepository)
    {
        // If already logged in and has 2FA set up.
        if ($this->getUser() && $this->getUser()->getTwoFASecret()) {
            return $this->redirectToRoute('home');
        }

        $submittedToken = $requestStack->getCurrentRequest()->get('token');
        if (!$this->isCsrfTokenValid('2fa', $submittedToken)) {
            throw $this->createAccessDeniedException();
        }

        $session = $requestStack->getSession();
        $secret = $session->get($this->twoFASessionKey);
        $session->remove($this->twoFASessionKey);
        $key = $requestStack->getCurrentRequest()->get('verification');
        if (!$secret || !$key || !$this->getUser()) {
            $this->addFlash(self::FLASH_NOTICE, '2fa-absent-verification');
            return $this->redirectToRoute('2fa_get');
        }

        if ($userRepository->checkTwoFA($secret, $key)) {
            $this->getUser()->setTwoFASecret($secret);
            $userRepository->save($this->getUser());
            return $this->redirectToRoute('home');
        }

        $this->addFlash(self::FLASH_NOTICE, '2fa-invalid-verification');
        return $this->redirectToRoute('2fa_get');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('home');
    }
}
