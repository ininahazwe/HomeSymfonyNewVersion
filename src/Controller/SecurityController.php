<?php

namespace App\Controller;

use App\Repository\AuthLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils,
                          AuthLogRepository $authLogRepository,
                          Request $request): Response
    {
        if ($this->getUser()) {
            $this->addFlash('error', 'Already logged in');
            return $this->redirectToRoute('home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $userIP = $request->getClientIp();

        $countOfRecentLoginFail = 0;

        if($lastUsername) {
            $countOfRecentLoginFail = $authLogRepository->getRecentAuthAttemptFailure($lastUsername, $userIP);
        }

        return $this->render('security/login.html.twig', [
            'countOfRecentLoginFail' => $countOfRecentLoginFail,
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
