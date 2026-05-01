<?php

namespace App\Controller\Admin;

use App\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TripCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Trip::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Trip')
            ->setEntityLabelInPlural('Trips');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('departure');
        yield TextField::new('destination');
        yield TextField::new('eaDeparture', 'Departure')
            ->setVirtual(true)
            ->formatValue(static function (mixed $value, ?Trip $entity): string {
                $dt = $entity?->getDepartureDateTime();

                return $dt instanceof \DateTimeInterface ? $dt->format('d/m/Y H:i') : '';
            })
            ->hideOnForm();
        yield DateTimeField::new('departureDateTime', 'Departure')->onlyOnForms();
        yield IntegerField::new('seatsTotal');
        yield IntegerField::new('seatsAvailable');
        yield NumberField::new('pricePerSeat');
        yield BooleanField::new('isActive');
        yield AssociationField::new('driver')->autocomplete();
        yield TextField::new('eaCreatedAt', 'Created at')
            ->setVirtual(true)
            ->formatValue(static function (mixed $value, ?Trip $entity): string {
                $dt = $entity?->getCreatedAt();

                return $dt instanceof \DateTimeInterface ? $dt->format('d/m/Y H:i') : '';
            })
            ->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        $desactiver = Action::new('desactiver', 'Désactiver', 'bi bi-x-octagon-fill')
            ->linkToCrudAction('desactiver')
            ->asWarningAction()
            ->renderAsForm()
            ->displayIf(fn (?Trip $trip) => $trip && $trip->isActive());

        return $actions
            ->add(Crud::PAGE_INDEX, $desactiver)
            ->add(Crud::PAGE_DETAIL, $desactiver);
    }

    #[AdminRoute(path: '/{entityId}/desactiver', name: 'desactiver', options: ['methods' => ['POST']])]
    public function desactiver(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGeneratorInterface $adminUrlGenerator): Response
    {
        /** @var Trip|null $trip */
        $trip = $context->getEntity()->getInstance();
        if (!$trip instanceof Trip) {
            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $trip->setIsActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'Trip was deactivated (soft delete).');

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }
}
