<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Contenir;
use App\Entity\Livre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContenirType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite')
            ->add('commande', EntityType::class, [
                'class' => Commande::class,
                'choice_label' => 'id',
            ])
            ->add('livre', EntityType::class, [
                'class' => Livre::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contenir::class,
        ]);
    }
}
