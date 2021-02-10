<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UsersRepository;
use App\Service\SendEmail;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    private $entityManager;
    private $session;
    private $usersRepository;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, UsersRepository $usersRepository)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->usersRepository = $usersRepository;
    }

    /**
     * @Route("/forgot-password", name="app_forgot_password", methods={"GET", "POST"})
     * @param Request $request
     * @param SendEmail $sendEmail
     * @param TokenGeneratorInterface $tokenGenerator
     * @return Response
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendRecoveryLink(Request $request, SendEmail $sendEmail, TokenGeneratorInterface $tokenGenerator): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $user = $this->usersRepository->findOneBy([
                'email' => $form['email']->getData()
            ]);   

            /* We make a lure */

            if(!$user){
                $this->addFlash('success', 'An email has be sent for password reset');
                return $this->redirectToRoute('app_login');
            }
            $user->setForgotPasswordToken($tokenGenerator->generateToken())
                 ->setForgotPasswordTokenRequestedAt(new DateTimeImmutable('now'))
                 ->setForgotPasswordTokenMustBeVerifiedBefore(new DateTimeImmutable('+15 minutes'));

            $this->entityManager->flush();

            $sendEmail->send([
                'recipient_email' => $user->getEmail(),
                'subject'         => 'Password reset',
                'html_template'   => 'forgot_password/forgot_password_email.html.twig',
                'context'         => [
                    'user' => $user
                ]
            ]);
            $this->addFlash('success', 'An email has be sent for password reset');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/forgot_password_step_1.html.twig', [
            'forgotPasswordFormStep1' => $form->createView(),
        ]);
    }

    /**
     * @Route ("/forgot-password/{id<\d+>}/{token}", name="app_retrieve_credentials", methods={"GET"})
     */
    public function retrieveCredentialsFromTheURL(string $token, Users $user): RedirectResponse
    {
        $this->session->set('Reset-Password-Token-URL', $token);
        $this->session->set('Reset-Password-User-Email', $user->getEmail());

        return $this->redirectToRoute('app_reset_password');

    }

    /**
     * @Route("/reset-password", name="app_reset_password", methods={"GET", "POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function resetPassword(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        [
            'token' => $token,
            'userEmail' => $userEmail
        ] = $this->getCredentialsFromSession();

        $user = $this->usersRepository->findOneBy([
           'email' => $userEmail
        ]);

        if(!$user){
            return $this->redirectToRoute('app_forgot_password');
        }

        /** @var DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore */
        $forgotPasswordTokenMustBeVerifiedBefore = $user->getForgotPasswordTokenMustBeVerifiedBefore();

        if(($user->getForgotPasswordToken() === null) || ($user->getForgotPasswordToken() !== $token) || ($this->isNotRequestedInTime($forgotPasswordTokenMustBeVerifiedBefore))) {
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $user->setPassword($encoder->encodePassword($user, $form['password']->getData()));

            /* We clear the token to make it unusable */
            $user->setForgotPasswordToken(null);
            $user->setForgotPasswordTokenVerifiedAt(new DateTimeImmutable('now'));

            $this->entityManager->flush();

            $this->removeCredentialsFromSession();

            $this->addFlash('success', 'New password is set, you can log in !');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/forgot_password_step_2.html.twig', [
            'forgotPasswordFormStep2' => $form->createView(),
            'passwordMustBeModifiedBefore' => $this->passwordMustBeModifiedBefore($user)
        ]);
    }

    /**
     * Gets the user ID and token from the session
     *
     * @return array
     */
    private function getCredentialsFromSession(): array
    {
        return [
            'token' => $this->session->get('Reset-Password-Token-URL'),
            'userEmail' => $this->session->get('Reset-Password-User-Email')
        ];
    }

    /**
     * Validates or not the fact that the link was clicked in the allocated time.
     *
     * @param DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore
     * @return bool
     */
    private function isNotRequestedInTime(DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore): bool
    {
        return (new DateTimeImmutable('now') > $forgotPasswordTokenMustBeVerifiedBefore);
    }

    /**
     * Removes the user ID and token form the session
     *
     * @return void
     */
    private function removeCredentialsFromSession(): void
    {
        $this->session->remove('Reset-Password-Token-URl');
        $this->session->remove('Reset-Password-User-Email');
    }

    /**
     * Returns the time before which the password must be changed
     *
     * @param Users $users
     * @return string The time in this format: 12h00
     */
    private function passwordMustBeModifiedBefore(Users $user): string
    {
        /**@var DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore */
        $passwordMustBeModifiedBefore = $user->getForgotPasswordTokenMustBeVerifiedBefore();

        return $passwordMustBeModifiedBefore->format('H\hi');
    }
}