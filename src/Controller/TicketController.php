<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Ticket;
use App\Form\TicketType;
use Doctrine\ORM\Mapping\Id;
use Psr\Log\LoggerInterface;
use PhpParser\Node\Expr\Cast\Int_;
use App\Repository\TicketRepository;
use Doctrine\Persistence\ObjectManager;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function index(): Response
    {
        if ($this->getUser()) {
            // **_récupère les infos de l’utilisateur en cours_**
            $user = $this->getUser();
            $userMail = $this->getUser()->getUserIdentifier();
            $userPwd = $this->getUser()->getPassword();
            $userRole = $this->getUser()->getRoles();

            $this->log->info('EMAIL', array($userMail));
            $this->log->info('PASSWORD', array($userPwd));
            $this->log->info('ROLE', array($userRole));
        }

        
        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            $tickets = $this->ticketRepository->findAll();
        } else {
            $tickets = $this->ticketRepository->findBy(['user' => $user] );
        }
        

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

            $user = $this->getUser();

            $ticket->setTicketStatut('initial')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUser($user);

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


         /**
         * @Route("/pdf", name="ticket_pdf")
         */
        public function pdf() : Response
        {
            //Données utiles
            $user = $this->getUser();
            $tickets = $this->ticketRepository->findBy(['user' => $user] );

                //Configuration de Dompdf
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');

            //Instantiation de Dompdf
            $dompdf = new Dompdf($pdfOptions);

            //Récupération du contenu de la vue
            $html = $this->renderView('ticket/pdf.html.twig', [
                'tickets' => $tickets,
                'title' => "Bienvenue sur notre page PDF"
            ]);

            //Ajout du contenu de la vue dans le PDF
            $dompdf->loadHtml($html);

            //Configuration de la taille et de la largeur du PDF
            $dompdf->setPaper('A4', 'portrait');

            //Récupération du PDF
            $dompdf->render();

            //Création du fichier PDF
            $dompdf->stream("ticket.pdf", [
                "Attachment" => true
            ]);

            return new Response ('', 200, [
                'Content-Type' => 'application/pdf'
            ]);
        }

            /**
             * @Route("/excel", name="ticket_excel")
             */

            public function excel() : Response {

                //Données utiles
                $user = $this->getUser();
                $tickets = $this->ticketRepository->findBy(['user' => $user] );
    
    
                $spreadsheet = new Spreadsheet ();
                $sheet = $spreadsheet->getActiveSheet ();
                $sheet->setCellValue ('A1', 'Liste des tickets pour l\'utilisateur : ' . $user->getUsername());
                $sheet->mergeCells('A1:E1');
                $sheet->setTitle("Liste des tickets");
    
                //Set Column names
                $columnNames = [
                    'Id',
                    'Objet',
                    'Date de création',
                    'Department',
                    'Statut',
                ];
                $columnLetter = 'A';
                foreach ($columnNames as $columnName) {
                    $sheet->setCellValue ($columnLetter . '3', $columnName);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                    $columnLetter++;
                }
                 foreach ($tickets as $key => $ticket) {
                    $sheet->setCellValue ('A' . ($key + 4), $ticket->getId());
                    $sheet->setCellValue ('B' . ($key + 4), $ticket->getObject());
                    $sheet->setCellValue ('C' . ($key + 4), $ticket->getCreatedAt()->format('d/m/Y'));
                    $sheet->setCellValue ('D' . ($key + 4), $ticket->getDepartment()->getName());
                    $sheet->setCellValue ('E' . ($key + 4), $ticket->getTicketStatut());
                }
                //Création du fichier xlsx
                $writer = new Xlsx($spreadsheet);
    
                //Création d'un fichier temporaire
                $fileName = "Export_Tickets.xlsx";
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
    
                //créer le fichier excel dans le dossier tmp du systeme
                $writer->save($temp_file);
    
                //Renvoie le fichier excel
                return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
         }
    




   
}
