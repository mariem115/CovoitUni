<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('score', ChoiceType::class, [
                'label' => 'Votre note',
                'choices' => [
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir une note.'),
                    new Range(min: 1, max: 5),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Votre commentaire (facultatif)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => ' ',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'rating';
    }
}
