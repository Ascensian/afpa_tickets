<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Department;
use Doctrine\Persistence\ObjectManager;
use App\Faker\Provider\ImmutableDateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    /**
     * Hash
     *
     * @var UserPasswordHasherInterface
     */
    protected $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }


    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $faker = Factory::create('fr_FR');

        //Création de 5 utilisateurs

        for ($u=0; $u < 5; $u++) { 
            //Création d'un nouvel objet user
            $user = new User;

            $hash = $this->hasher->hashPassword($user, "hash");

            // Si premier utilisateur créé on lui donner le profil de admin

            if ($u === 0) {
                $user->setRoles(["ROLE_ADMIN"])
                ->setEmail("admin@test.test");
            } else {
                $user->setEmail("user($u)@test.test");
            }
            

        $user->setEmail($faker->safeEmail())
            ->setName($faker->name())
            ->setPassword($hash);

            $manager->persist($user);
        }

        // Création de 10 départements

        for ($d=0; $d < 10; $d++) { 
            // Création d'un nouvel objet
            $department = new Department;
            // On nourrit l'objet

            $department->setName($faker->company());
            // On persiste notre objet department

            $manager->persist($department);

        }

        // On push les departments en BDD

        $manager->flush();

        $allDepartments = $manager->getRepository(Department::class)
        ->findAll();
        
        //Création entre 30 et 50 tickets aléatoirement

        for($t = 0; $t < mt_rand(30, 50); $t++) {
            // Création d'un nouvel objet ticket

            $ticket = new Ticket();

            //On nourrit l'objet ticket

            $ticket->setMessage($faker->paragraph(3))
                    ->setComment($faker->paragraph(3))
                    ->setTicketStatut('initial')
                    ->setCreatedAt(new \DateTimeImmutable()) // attention les dates sont crées en fonction du réglage serveur
                    ->setFinishedAt($ticket->getTicketStatut() == 'finished' ? ImmutableDateTime::immutableDateTimeBetween('now', '6 months') : null)
                    ->setObject($faker->sentence(6))
                    ->setDepartment($faker->randomElement($allDepartments));
                // On fait persister les données

                $manager->persist($ticket);
                

        }

        $manager->flush();
    }
}
