<?php

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('note', IntegerType::class, [
                'label' => 'Note (sur 5)',
                'attr' => [
                    'min' => 1,
                    'max' => 5
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La note est obligatoire.',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'La note doit être comprise entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre avis',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le contenu de l\'avis ne peut pas être vide.',
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'minMessage' => 'Votre avis doit contenir au moins {{ limit }} caractères.',
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}
