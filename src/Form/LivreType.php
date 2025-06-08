<?php

// src/Form/LivreType.php

namespace App\Form;

use App\Entity\Livre;
use App\Entity\Auteur;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Ajout de l'import pour FileType
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // Ajout de l'import pour la contrainte File

class LivreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du livre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix (€)',
                'currency' => 'EUR',
            ])
            // Champ pour l'URL de l'image
            ->add('image', TextType::class, [
                'label' => 'URL de l\'image du livre',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez l\'URL de l\'image']
            ])
            ->add('auteur', EntityType::class, [
                'class' => Auteur::class,
                'choice_label' => 'nom',
                'label' => 'Auteur',
                'placeholder' => 'Choisir un auteur',
            ])
            // Modification de ce champ pour afficher des cases à cocher et permettre la sélection multiple
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégories',
                'multiple' => true,  // Permet de sélectionner plusieurs catégories
                'expanded' => true,  // Utilise des cases à cocher
            ])
            // Champ pour le fichier numérique avec validation
            ->add('fichierNumerique', FileType::class, [
                'required' => false,
                'mapped' => false, // ne lie pas ce champ directement à l'entité
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf', 'application/epub+zip'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF ou ePub valide',
                    ])
                ]
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livre::class,
        ]);
    }
}
