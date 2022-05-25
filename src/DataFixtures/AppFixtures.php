<?php

namespace App\DataFixtures;

use App\Entity\Department;
use Faker\Factory;
use App\Entity\Ticket;
use App\Faker\Provider\ImmutableDateTime;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $faker = Factory::create('fr_FR');

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
                    ->setIsActive($faker->boolean(75))
                    ->setCreatedAt(new \DateTimeImmutable()) // attention les dates sont crées en fonction du réglage serveur
                    ->setFinishedAt(!$ticket->isIsActive() ? ImmutableDateTime::immutableDateTimeBetween('now', '6 months') : null)
                    ->setObject($faker->sentence(6))
                    ->setDepartment($faker->randomElement($allDepartments));
                // On fait persister les données

                $manager->persist($ticket);
                

        }

        $manager->flush();
    }
}
