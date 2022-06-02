<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    protected TicketRepository $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * @Route("/{_locale}/", name="app_home", requirements={"_locale": "en|fr"})
     */
    public function index(): Response
    {
        $countActiveTicket = count($this->ticketRepository->getAllWithStatus('initial'));
        $countNoActiveTicket = count($this->ticketRepository->getAllWithStatus('finished'));
        
        // count($this->ticketRepository->findBy(['ticketStatut' => true]));

        $tabDep = [];
        $tabTickets = [];

        $countDepGroupBy = $this->ticketRepository->getAllDep();

            foreach($countDepGroupBy as $key=> $tickets){
                $tabTickets[] = $tickets[1];
                $tabDep[] = "\"" . $tickets[2] . "\"";  
            }

            return $this->render('home/index.html.twig', [
                'countActive' => $countActiveTicket,
                'countNoActive' => $countNoActiveTicket,
                'countDep' => $countDepGroupBy,
                'nbTickets' => $tabTickets,
                'nameDep'=> implode("," , $tabDep),
            
             ]);
    }

        /**
    * @Route("/")
    */
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('app_home', ['_locale' => 'fr']);
    }
}
