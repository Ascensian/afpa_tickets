<?php

namespace App\Controller;


use App\Repository\TicketRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
     * @Route("/Hugoticket", name="app_ticket")
     */
    public function index(TicketRepository $repository): Response
    {
       
        $tickets = $repository->findAll();

        dd($tickets);

        return $this->render('ticket/index.html.twig', [
            'controller_name' => 'TicketController',
        ]);
    }
}
