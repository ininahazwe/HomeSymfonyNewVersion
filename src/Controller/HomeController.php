<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\CategoriesRepository;
use App\Repository\ProjectsRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(ProjectsRepository $projectsRepository, CategoriesRepository $categoriesRepository)
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $projects = $projectsRepository->findAll();
        $category = $categoriesRepository->findAll();
        $user = $this->getUser();
        return $this->render('home/index.html.twig', [
            'projects' => $projects,
            'user' => $user,
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function contact(Request $request, MailerInterface $mailer)
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->getData();

            // On crée le message
            $email = (new TemplatedEmail())
                ->from('yvesininahazwe@gmail.com')
                ->to('home.internationalprojects@gmail.com')
                ->subject('Time for Symfony Mailer!')
                ->text('Sending emails is fun again!')
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'contact' => $contact
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Your message has been sent'); // Permet un message flash de renvoi

            $this->redirectToRoute('home');
        }
        return $this->render('home/contact.html.twig',[
            'contactForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/mentions-legales", name="mentions")
     */
    public function mentions()
    {
        return $this->render('home/mentions-legales.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }
}
