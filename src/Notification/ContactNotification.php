<?php
namespace App\Notification;

use App\Entity\Contact;
use Symfony\Component\Mailer\Mailer;
use Twig\Environment;

class ContactNotification {

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $renderer;

    public function __construct(Mailer $mailer, Environment $renderer){

        $this->mailer = $mailer;
        $this->renderer = $renderer;
    }

    public function notify(Contact $contact){
        $message = (new Mailer('Home:' . $contact->getMessage()))
            ->setForm('noreply@home-association.fr')
            ->setTo('yves@cri-paris.org')
            ->setReplyTo($contact->getEmail())
            ->setBody($this->renderer->render('emails/contact.html.twig', [
                'contact' => $contact
            ]), 'text/html');
        $this->mailer->send($message);
    }
}