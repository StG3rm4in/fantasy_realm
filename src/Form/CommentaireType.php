<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'choices' => [
                    '⭐ 1 étoile' => 1,
                    '⭐⭐ 2 étoiles' => 2,
                    '⭐⭐⭐ 3 étoiles' => 3,
                    '⭐⭐⭐⭐ 4 étoiles' => 4,
                    '⭐⭐⭐⭐⭐ 5 étoiles' => 5,
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'visually-hidden'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez sélectionner une note'),
                    new Assert\Range(
                        min: 1,
                        max: 5,
                        notInRangeMessage: 'La note doit être entre {{ min }} et {{ max }}'
                    )
                ]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Votre avis',
                'attr' => [
                    'id' => 'commentaire-textarea',
                    'placeholder' => 'Partagez votre avis sur ce personnage...',
                    'class' => 'form-control',
                    'rows' => 5,
                    'maxlength' => 1000
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le commentaire est requis'),
                    new Assert\Length(
                        min: 10,
                        max: 1000,
                        minMessage: 'Votre avis doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Votre avis ne peut pas dépasser {{ limit }} caractères'
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}


