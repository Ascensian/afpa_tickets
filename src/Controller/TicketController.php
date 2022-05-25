<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Persistence\ObjectManager;
use PhpParser\Node\Expr\Cast\Int_;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/ticket")
 */

class TicketController extends AbstractController
{
    /**
     * 
     *
     * @var TicketRepository
     */
    protected $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }
    /**
     * @Route("/", name="app_ticket")
     */
    public function index(TicketRepository $repository): Response
    {
       
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

            $ticket->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable());

            $title = "Création d'un ticket";

        } else {
            $title = "Update du formulaire : {$ticket->getId()}" ; 
        }

        
        
        $form = $this->createForm(TicketType::class, $ticket, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dump("OKAY");
            // dd($form);
            $ticket->setObject($form['object']->getData())
                    ->setMessage($form['message']->getData())
                    ->setDepartment($form['department']->getData());

                    // $manager->persist($ticket);
                    // $manager->flush();

                    $this->ticketRepository->add($ticket, true);


        }
        // dd($form);

        return $this->render('ticket/create.html.twig', [
            'form' => $form->createView(),
            'title' => $title
            
        ]);

    }

   
}
