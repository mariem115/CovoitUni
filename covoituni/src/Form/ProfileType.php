<?php

namespace App\Form;

use App\Entity\User;
use App\Service\LocationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $includeVehicle = (bool) ($options['include_vehicle_fields'] ?? false);

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => ' '],
                'constraints' => [
                    new Length(max: 100),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => ' '],
                'constraints' => [
                    new Length(max: 100),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => ' '],
            ])
            ->add('university', ChoiceType::class, [
                'label' => 'Université',
                'choices' => LocationData::getUniversitiesGroupedChoices(),
                'placeholder' => '-- Choisir votre université --',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'data-searchable' => 'true',
                ],
            ]);

        if ($includeVehicle) {
            $builder
            ->add('vehiculeMarque', TextType::class, [
                'label' => 'Marque du véhicule',
                'required' => false,
                'attr' => ['placeholder' => ' '],
                'constraints' => [
                    new Length(max: 100),
                ],
            ])
            ->add('vehiculeModele', TextType::class, [
                'label' => 'Modèle du véhicule',
                'required' => false,
                'attr' => ['placeholder' => ' '],
                'constraints' => [
                    new Length(max: 100),
                ],
            ])
            ->add('vehiculeCouleur', TextType::class, [
                'label' => 'Couleur du véhicule',
                'required' => false,
                'attr' => ['placeholder' => ' '],
                'constraints' => [
                    new Length(max: 50),
                ],
            ])
            ->add('vehiculeAnnee', IntegerType::class, [
                'label' => 'Année du véhicule',
                'required' => false,
                'attr' => ['placeholder' => ' '],
            ]);
        }

        $builder
            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => ' ',
                    'class' => 'form-control',
                ],
            ])
            ->add('profilePhoto', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : JPEG, PNG, WebP.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_vehicle_fields' => false,
        ]);
        $resolver->setAllowedTypes('include_vehicle_fields', 'bool');
    }
}
