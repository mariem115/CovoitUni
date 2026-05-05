<?php

namespace App\Form;

use App\Entity\Trip;
use App\Service\LocationData;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $villesChoices = LocationData::getChoiceMap();

        $builder
            ->add('departure', ChoiceType::class, [
                'label' => 'Ville de départ',
                'choices' => $villesChoices,
                'placeholder' => '-- Choisir une ville --',
                'attr' => [
                    'class' => 'form-select ville-select',
                    'data-searchable' => 'true',
                ],
                'constraints' => [
                    new NotBlank(message: 'Indiquez la ville de départ.'),
                ],
            ])
            ->add('destination', ChoiceType::class, [
                'label' => 'Destination',
                'choices' => $villesChoices,
                'placeholder' => '-- Choisir une ville --',
                'attr' => [
                    'class' => 'form-select ville-select',
                    'data-searchable' => 'true',
                ],
                'constraints' => [
                    new NotBlank(message: 'Indiquez la destination.'),
                ],
            ])
            ->add('departureDateTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de départ',
                'input' => 'datetime_immutable',
                'html5' => true,
                'constraints' => [
                    new NotBlank(message: 'Choisissez la date et l’heure.'),
                    new GreaterThan(
                        value: 'today',
                        message: 'La date du trajet doit être après aujourd’hui.',
                        groups: ['trip_create'],
                    ),
                ],
            ])
            ->add('seatsTotal', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1, 'max' => 8],
                'constraints' => [
                    new NotBlank(),
                    new Range(min: 1, max: 8),
                ],
            ])
            ->add('pricePerSeat', MoneyType::class, [
                'label' => 'Prix par place (laisser vide si gratuit)',
                'currency' => 'TND',
                'required' => false,
                'divisor' => 1,
                'html5' => true,
                'input' => 'string',
                'empty_data' => null,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / infos utiles',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
