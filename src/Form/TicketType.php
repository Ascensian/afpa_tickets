<?php

namespace App\Form;

use App\Entity\Department;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('object', TextType::class, [
                'label' => 'Objet du ticket',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('message')
            // ->add('comment')
            // ->add('ticketStatut')
            // ->add('createdAt', DateTimeType::class, [
            //     'widget' => 'single_text'
            // ])
            // ->add('finishedAt')
            
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                },
                'choice_label' => 'name'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Soumettre'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
