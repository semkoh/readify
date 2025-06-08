<?php
// src/Form/CommandeType.php

namespace App\Form;
use App\Entity\Utilisateur;
use App\Entity\Commande;
use App\Entity\Livre; // Ajout de l'entité Livre
use App\Entity\Contenir; // Lien avec Contenir
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateCommande', null, [
                'widget' => 'single_text',  // Format de la date
            ])
            ->add('prixTotal', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Total Price'],
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'en cours',
                    'Payée' => 'payée',
                ],
                'placeholder' => 'Sélectionner l\'état',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('datePaiement', null, [
                'widget' => 'single_text', // Format de la date
                'required' => false,       // Champ optionnel
                'attr' => ['class' => 'form-control'],
            ])
            ->add('methodePaiement', ChoiceType::class, [
                'choices' => [
                    'PayPal' => 'paypal',
                ],
                'placeholder' => 'Sélectionner la méthode de paiement',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',  // Affiche l'ID de l'utilisateur
                'attr' => ['class' => 'form-control'],
            ])
            // Sélectionner les livres à ajouter à la commande via Contenir
            ->add('livres', EntityType::class, [
                'class' => Livre::class,
                'choice_label' => 'titre',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false, // pour éviter l’erreur
                'required' => false,
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,  // Associe le formulaire à l'entité Commande
        ]);
    }
}
