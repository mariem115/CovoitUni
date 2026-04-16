<?php

namespace App\Form;

use App\Entity\Trip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departure', TextType::class, [
                'label' => 'Ville de départ',
                'attr' => ['placeholder' => 'Ex: Tunis'],
                'constraints' => [
                    new NotBlank(message: 'Indiquez la ville de départ.'),
                    new Length(max: 200),
                ],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['placeholder' => 'Ex: Sousse'],
                'constraints' => [
                    new NotBlank(message: 'Indiquez la destination.'),
                    new Length(max: 200),
                ],
            ])
            ->add('departureDateTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de départ',
                'input' => 'datetime_immutable',
                'html5' => true,
                'constraints' => [
                    new NotBlank(message: 'Choisissez la date et l’heure.'),
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
