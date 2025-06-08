<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Enum\UtilisateurRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('nom', TextType::class, [
            'label' => 'Nom',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new NotBlank(['message' => 'Le nom est requis.']),
            ],
        ])
        ->add('prenom', TextType::class, [
            'label' => 'Prénom',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new NotBlank(['message' => 'Le prénom est requis.']),
            ],
        ])
        ->add('email', EmailType::class, [
            'label' => 'Adresse email',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new NotBlank(['message' => 'L\'adresse email est requise.']),
            ],
        ])
        ->add('plainPassword', PasswordType::class, [
            'mapped' => false,
            'label' => 'Mot de passe',
            'attr' => [
                'autocomplete' => 'new-password',
                'class' => 'form-control'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractères.',
                    'max' => 4096,
                ]),
            ],
        ])
        // Conditionner l'affichage du champ 'role' pour un utilisateur
        ->add('role', ChoiceType::class, [
            'label' => 'Rôle',
            'choices' => UtilisateurRole::cases(),
            'choice_label' => fn(UtilisateurRole $role) => ucfirst($role->value),
            'choice_value' => fn(?UtilisateurRole $role) => $role?->value,
            'attr' => ['class' => 'form-select'],
            'disabled' => true, // Désactiver le choix de rôle
        ])
        ->add('agreeTerms', CheckboxType::class, [
            'mapped' => false,
            'label' => "J'accepte les conditions",
            'constraints' => [
                new IsTrue(['message' => 'Vous devez accepter les conditions.']),
            ],
        ]);
}


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
