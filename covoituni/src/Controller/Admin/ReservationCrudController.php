<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reservation')
            ->setEntityLabelInPlural('Reservations')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(
            ChoiceFilter::new('status')->setChoices([
                'Pending' => 'pending',
                'Confirmed' => 'confirmed',
                'Cancelled' => 'cancelled',
            ])
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('passenger')->autocomplete();
        yield AssociationField::new('trip')->autocomplete();
        yield ChoiceField::new('status')
            ->setChoices([
                'Pending' => 'pending',
                'Confirmed' => 'confirmed',
                'Cancelled' => 'cancelled',
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'confirmed' => 'success',
                'cancelled' => 'danger',
            ]);
        yield IntegerField::new('seatsBooked');
        yield TextField::new('eaCreatedAt', 'Created at')
            ->setVirtual(true)
            ->formatValue(static function (mixed $value, ?Reservation $entity): string {
                $dt = $entity?->getCreatedAt();

                return $dt instanceof \DateTimeInterface ? $dt->format('d/m/Y H:i') : '';
            })
            ->hideOnForm();
        yield BooleanField::new('isRated');
    }
}
