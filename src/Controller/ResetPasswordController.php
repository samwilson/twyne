<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Settings;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends ControllerBase
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;

    public function __construct(bool $requireTwoFactorAuth, ResetPasswordHelperInterface $resetPasswordHelper)
    {
        parent::__construct($requireTwoFactorAuth);
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * @Route("/reset", name="reset")
     */
    public function request(
        Request $request,
        MailerInterface $mailer,
        UserRepository $userRepository,
        Settings $settings
    ): Response {
        // For GET requests, display the 'request' button.
        if (!$request->isMethod('POST')) {
            return $this->render('reset_password/request.html.twig', []);
        }
        // Otherwise, send the request confirmation email.
        // Check the CSRF token.
        if (!$this->isCsrfTokenValid('reset_request', $request->get('csrf_token'))) {
            throw new Exception('Invalid CSRF token');
        }
        // Find the user.
        $user = $userRepository->findOneBy(['username' => $request->get('username')]);
        if (!$user) {
            // Pretend, if there's no user with this username.
            return $this->redirectToRoute('reset_check');
        }
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // Pretend, if anything went wrong.
            return $this->redirectToRoute('reset_check');
        }
        // Send the email.
        $email = (new TemplatedEmail())
            ->from(new Address($settings->getMailFrom()))
            ->to($user->getEmail())
            ->subject('[' . $settings->siteName() . '] Password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context(['resetToken' => $resetToken]);
        $mailer->send($email);
        // Store the token for later checking.
        $this->setTokenObjectInSession($resetToken);
        // Show a 'check email' confirmation message.
        return $this->redirectToRoute('reset_check');
    }

    /**
     * @Route("/reset-check", name="reset_check")
     */
    public function checkEmail(Request $request): Response
    {
        $resetToken = $this->getTokenObjectFromSession();
        if (!$resetToken) {
            return $this->redirectToRoute('reset');
        }
        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset-token/{token}", name="reset_token")
     */
    public function reset(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        string $token = null
    ): Response {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('reset_token');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash(self::FLASH_ERROR, $e->getReason());
            return $this->redirectToRoute('reset');
        }

        if (!$request->isMethod('post')) {
            return $this->render('reset_password/reset.html.twig', []);
        }

        // Check the CSRF token.
        if (!$this->isCsrfTokenValid('reset_password', $request->get('csrf_token'))) {
            throw new Exception('Invalid token');
        }

        $pass = $request->get('password');
        $pass2 = $request->get('password_verification');
        if (!$pass || !$pass2 || $pass !== $pass2) {
            $this->addFlash(self::FLASH_ERROR, 'Passwords do not match.');
            return $this->redirectToRoute('reset_token');
        }

        // A password reset token should be used only once, remove it.
        $this->resetPasswordHelper->removeResetRequest($token);

        // Encode the plain password, and set it.
        $user->setPassword($passwordEncoder->encodePassword($user, $pass));
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        $this->cleanSessionAfterReset();
        $this->addFlash(self::FLASH_SUCCESS, 'Password changed. You can now log in with your new password.');
        return $this->redirectToRoute('login');
    }
}
