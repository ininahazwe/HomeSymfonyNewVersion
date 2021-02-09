<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Service\SendEmail;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        EntityManagerInterface $entityManager,
        Request $request,
        SendEmail $sendEmail,
        TokenGeneratorInterface $tokenGenerator,
        UserPasswordEncoderInterface $passwordEncoder
    ): Response
    {
        $user = new Users();

        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registrationToken = $tokenGenerator->generateToken();
            $user->setRegistrationToken($registrationToken)
                // encode the plain password
                ->setPassword($passwordEncoder->encodePassword($user, $form->get('password')->getData()));

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            $sendEmail->send([
                'recipient_email' => $user->getEmail(),
                'subject'         => "Email verification for account validation",
                'html_template'   => "registration/register_confirmation_email.html.twig",
                'context'         => [
                    'userID'            => $user->getId(),
                    'registrationToken' => $registrationToken,
                    'tokenLifeTime'     => $user->getAccountMustBeVerifiedBefore()->format('d/m/Y à H:i')
                ]
            ]);

            $this->addFlash('success', "Your account has been created. Check your mailbox for activation");

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id<\d+>}/{token}", name="app_verify_account", methods={"GET"})
     */

    public function verifyUserAccount(EntityManagerInterface $entityManager, Users $users, string $token): Response
    {
        if(($users->getRegistrationToken() === null) || ($users->getRegistrationToken() !==
                $token) || ($this->isNotRequestedInTime($users->getAccountMustBeVerifiedBefore())))
        {
            throw new AccessDeniedException();
        }
        $users->setIsVerified(true);
        $users->setAccountVerifiedAt(new DateTimeImmutable('now'));
        $users->setRegistrationToken(null);
        $entityManager->flush();
        $this->addFlash('success', 'Your account is now activated');

        return $this->redirectToRoute('app_login');
    }
    private function isNotRequestedInTime(\DateTimeImmutable $accountMustBeVerifiedBefore):bool
    {
        return (new \DateTimeImmutable('now') > $accountMustBeVerifiedBefore);
    }
}
