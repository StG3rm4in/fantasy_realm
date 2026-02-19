<?php

namespace App\Form;

use App\Entity\Personnage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PersonnageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du personnage',
                'attr' => [
                    'placeholder' => 'ex: Aragorn le Courageux',
                    'class' => 'form-control',
                    'maxlength' => 50
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est requis'),
                    new Assert\Length(
                        min: 2,
                        max: 50,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    )
                ]
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Masculin' => 'Masculin',
                    'Féminin' => 'Féminin',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un genre',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le genre est requis')
                ]
            ])
            ->add('couleurPeau', ChoiceType::class, [
                'label' => 'Couleur de peau',
                'choices' => [
                    'Pâle' => 'pale',
                    'Claire' => 'claire',
                    'Beige' => 'beige',
                    'Olive' => 'olive',
                    'Bronze' => 'bronze',
                    'Brune' => 'brune',
                    'Foncée' => 'foncee',
                    'Ébène' => 'ebene',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une couleur',
                'constraints' => [
                    new Assert\NotBlank(message: 'La couleur de peau est requise')
                ]
            ])
            ->add('couleurYeux', ChoiceType::class, [
                'label' => 'Couleur des yeux',
                'choices' => [
                    'Marron' => 'marron',
                    'Bleu' => 'bleu',
                    'Vert' => 'vert',
                    'Noisette' => 'noisette',
                    'Gris' => 'gris',
                    'Ambre' => 'ambre',
                    'Violet' => 'violet',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une couleur',
                'constraints' => [
                    new Assert\NotBlank(message: 'La couleur des yeux est requise')
                ]
            ])
            ->add('couleurCheveux', ChoiceType::class, [
                'label' => 'Couleur des cheveux',
                'choices' => [
                    'Noir' => 'noir',
                    'Brun foncé' => 'brun-fonce',
                    'Brun' => 'brun',
                    'Châtain' => 'chatain',
                    'Blond foncé' => 'blond-fonce',
                    'Blond' => 'blond',
                    'Blond platine' => 'blond-platine',
                    'Roux' => 'roux',
                    'Auburn' => 'auburn',
                    'Gris' => 'gris',
                    'Blanc' => 'blanc',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une couleur',
                'constraints' => [
                    new Assert\NotBlank(message: 'La couleur des cheveux est requise')
                ]
            ])
            ->add('formeYeux', ChoiceType::class, [
                'label' => 'Forme des yeux',
                'choices' => [
                    'Ronds' => 'ronds',
                    'Amande' => 'amande',
                    'Bridés' => 'brides',
                    'Tombants' => 'tombants',
                    'En amande allongée' => 'amande-allongee',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une forme',
                'constraints' => [
                    new Assert\NotBlank(message: 'La forme des yeux est requise')
                ]
            ])
            ->add('formeNez', ChoiceType::class, [
                'label' => 'Forme du nez',
                'choices' => [
                    'Droit' => 'droit',
                    'Aquilin' => 'aquilin',
                    'Retroussé' => 'retrousse',
                    'Large' => 'large',
                    'Fin' => 'fin',
                    'Plat' => 'plat',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une forme',
                'constraints' => [
                    new Assert\NotBlank(message: 'La forme du nez est requise')
                ]
            ])
            ->add('formeBouche', ChoiceType::class, [
                'label' => 'Forme de la bouche',
                'choices' => [
                    'Fine' => 'fine',
                    'Moyenne' => 'moyenne',
                    'Pulpeuse' => 'pulpeuse',
                    'Large' => 'large',
                    'Petite' => 'petite',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une forme',
                'constraints' => [
                    new Assert\NotBlank(message: 'La forme de la bouche est requise')
                ]
            ])
            ->add('partage', ChoiceType::class, [
                'label' => 'Visibilité du personnage',
                'choices' => [
                    'Privé (visible uniquement par vous)' => false,
                    'Public (visible dans la galerie communautaire)' => true,
                ],
                'expanded' => true,
                'data' => false,
                'label_attr' => ['class' => 'form-label fw-semibold']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
        ]);
    }
}
