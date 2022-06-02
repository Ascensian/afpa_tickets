<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use Doctrine\ORM\Mapping\Id;
use PhpParser\Node\Expr\Cast\Int_;
use App\Repository\TicketRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Registry;

/**
 * @Route("{_locale}/ticket", requirements={"_locale": "en|fr"})
 */

class TicketController extends AbstractController
{
    /**
     * 
     *
     * @var TicketRepository
     */
    protected $ticketRepository;
    protected TranslatorInterface $translator;
    protected LoggerInterface $log;
    protected MailerInterface $mailer;
    protected $registry;
    

    public function __construct(TicketRepository $ticketRepository, TranslatorInterface $translator, LoggerInterface $log, MailerInterface $mailer, Registry $registry)
    {
        $this->ticketRepository = $ticketRepository;
        $this->translator = $translator;
        $this->log = $log;
        $this->mailer = $mailer;
        $this->registry = $registry;
    }
    /**
     * @Route("/", name="app_ticket")
     */
    public function index(TicketRepository $repository): Response
    {
        $userMail = $this->getUser()->getUserIdentifier();
        $userPwd = $this->getUser()->getPassword();
        $userRole = $this->getUser()->getRoles();

        $this->log->info('EMAIL', array($userMail));
        $this->log->info('PASSWORD', array($userPwd));
        $this->log->info('ROLE', array($userRole));


       
        $tickets = $repository->findAll();

        //dd($tickets);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * @Route("/create", name="ticket_create")
     * @Route("/update/{id}", name="ticket_update", requirements={"id"="\d+"})
     */

    public function createTicket(Ticket $ticket = null, Request $request) {
        // dd($request);

        if (!$ticket) {
            $ticket = new Ticket;

            $ticket->setTicketStatut('initial')
            ->setCreatedAt(new \DateTimeImmutable());

            // $title = "Création d'un ticket";

            $title = $this->translator->trans("title.ticket.create");

        } else {
            $workflow = $this->registry->get($ticket, 'ticketTraitement');

            if($ticket->getTicketStatut() != 'wip' ) {
                $workflow->apply($ticket, 'to_wip');
            }

            $title = "Update du formulaire : {$ticket->getId()}" ;
            $title = $this->translator->trans("title.ticket.update") . "{$ticket->getId()}";
        }

        
        
        $form = $this->createForm(TicketType::class, $ticket, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dump("OKAY");
            // dd($form);


            if ($request->attributes->get("_route")==="ticket_create") {
                MailerController::sendEmail($this->mailer, "user1@test.fr", "Ticket ajouté", "a bien été ajouté", $ticket);
                $this->addFlash(
                    'success',
                    'Votre ticket a bien été ajouté');
                    
            } else {
                $this->addFlash(
                    'info',
                    'Votre ticket a bien été mis à jour');
                    MailerController::sendEmail($this->mailer, "user1@test.fr", "Ticket modifié", "a bien été modifié", $ticket);
            }

            $ticket->setObject($form['object']->getData())
                    ->setMessage($form['message']->getData())
                    ->setDepartment($form['department']->getData());


            
                    // $manager->persist($ticket);
                    // $manager->flush();

                    $this->ticketRepository->add($ticket, true);
                    return $this->redirectToRoute('app_ticket');


        }
        

        return $this->render('ticket/create.html.twig', [
            'form' => $form->createView(),
            'title' => $title
            
        ]);

    }

    /**
     * @Route("/delete/{id}", name="ticket_delete", requirements={"id"="\d+"})
     */

    public function deleteTicket(Ticket $ticket): Response
    {
        MailerController::sendEmail($this->mailer, "user1@test.fr", "Ticket Supprimé", " a bien été supprimé", $ticket);

        $this->ticketRepository->remove($ticket, true);

        $this->addFlash('danger', 'Votre ticket a bien été supprimé');
        return $this->redirectToRoute('app_ticket');
    }

    /** 
     * @Route("/close/{id}", name="ticket_close",requirements={"id"="\d+"})
     */
    public function closeTicket(Ticket $ticket): Response
    {
        $workflow = $this->registry->get($ticket, 'ticketTraitement');
        $workflow->apply($ticket, 'to_finished');
        $this->ticketRepository->add($ticket, true);

        return $this->redirectToRoute('app_ticket');
    }

     /**
      * @Route("/details/{id}", name="ticket_detail", requirements={"id"="\d+"})
      */
        public function detailTicket(Ticket $ticket) : Response
        {
            //dd($ticket);
            return $this->render('ticket/detail.html.twig', ['ticket' => $ticket]);
        }


   
}
