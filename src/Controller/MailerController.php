<?php

namespace App\Controller;

use App\Entity\Ticket;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MailerController extends AbstractController
{
    /**
     * @Route("/mailer", name="app_mailer")
     */
    public function index(): Response
    {
        return $this->render('mailer/index.html.twig', [
            'controller_name' => 'MailerController',
        ]);
    }

     # [Route('/email')]
     public static function sendEmail(MailerInterface $mailer, string $mailTo, string $subject, string $text, Ticket $ticket) : void
     { 
 
         $email = (new TemplatedEmail())
             ->from('hello@example.com')
             ->to($mailTo)
             //->cc('cc@example.com')
             //->bcc('bcc@example.com')
             //->replyTo('fabien@example.com')
             //->priority(Email::PRIORITY_HIGH)
             ->subject($subject)
             ->text($text)
             ->htmlTemplate('email/emailClient.html.twig')
             ->context(['text'=>$text, 'ticket' =>$ticket]);
 
         $mailer->send($email);
 
         // ...
     }
}
