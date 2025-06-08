<?php
// src/Form/UtilisateurType.php
namespace App\Form;

use App\Entity\Utilisateur;
use App\Enum\UtilisateurRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'Laissez vide pour ne pas modifier'],
            ])
            ->add('role', EnumType::class, [
                'class' => UtilisateurRole::class,
                'label' => 'Rôle',
                'placeholder' => 'Choisir un rôle',
                'choice_label' => fn(UtilisateurRole $role) => ucfirst($role->value),
            ])
            ->add('dateDesactivation');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
