<?php

namespace App\Controller\Admin;

use App\Entity\Rating;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RatingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Rating::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Rating')
            ->setEntityLabelInPlural('Ratings')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield AssociationField::new('reviewer')->autocomplete();
        yield AssociationField::new('driver')->autocomplete();
        yield IntegerField::new('score')
            ->formatValue(function ($value) {
                if (null === $value) {
                    return '';
                }
                $n = max(0, min(5, (int) $value));

                return str_repeat('★', $n).str_repeat("\u{2606}", 5 - $n).' ('.$n.'/5)';
            });
        yield TextareaField::new('comment');
        yield TextField::new('eaCreatedAt', 'Created at')
            ->setVirtual(true)
            ->formatValue(static function (mixed $value, ?Rating $entity): string {
                $dt = $entity?->getCreatedAt();

                return $dt instanceof \DateTimeInterface ? $dt->format('d/m/Y H:i') : '';
            })
            ->hideOnForm();
    }
}
