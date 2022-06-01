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

    public function __construct(TicketRepository $ticketRepository, TranslatorInterface $translator)
    {
        $this->ticketRepository = $ticketRepository;
        $this->translator = $translator;
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

            // $title = "Création d'un ticket";

            $title = $this->translator->trans("title.ticket.create");

        } else {
            $title = "Update du formulaire : {$ticket->getId()}" ;
            $title = $this->translator->trans("title.ticket.update") . "{$ticket->getId()}";
        }

        
        
        $form = $this->createForm(TicketType::class, $ticket, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dump("OKAY");
            // dd($form);


            if ($request->attributes->get("_route")==="ticket_create") {
                $this->addFlash(
                    'success',
                    'Votre ticket a bien été ajouté');
                    
            } else {
                $this->addFlash(
                    'info',
                    'Votre ticket a bien été mis à jour');
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
        $this->ticketRepository->remove($ticket, true);

        $this->addFlash('danger', 'Votre ticket a bien été supprimé');
        return $this->redirectToRoute('app_ticket');
    }

   
}
