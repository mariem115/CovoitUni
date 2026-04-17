<?php

namespace App\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Choice-style filter for JSON "roles" stored as ["ROLE_ADMIN"] (MySQL JSON + Doctrine).
 * Uses LIKE patterns so the query stays valid DQL (JSON_CONTAINS is not portable in DQL).
 */
final class UserStoredRolesFilter implements FilterInterface
{
    use FilterTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName = 'roles', $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle')
            ->setFormTypeOption('value_type_options.choices', [
                'Student (no ROLE_ADMIN stored)' => 'student',
                'Administrator (ROLE_ADMIN)' => 'admin',
            ]);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $property = $filterDataDto->getProperty();
        $value = $filterDataDto->getValue();
        $parameterName = $filterDataDto->getParameterName();

        if (null === $value || '' === $value) {
            return;
        }

        $field = sprintf('%s.%s', $alias, $property);

        if ('admin' === $value) {
            $queryBuilder
                ->andWhere(sprintf('%s LIKE :%s', $field, $parameterName))
                ->setParameter($parameterName, '%ROLE_ADMIN%');

            return;
        }

        if ('student' === $value) {
            $queryBuilder
                ->andWhere(sprintf('(%s NOT LIKE :%s OR %s IS NULL)', $field, $parameterName, $field))
                ->setParameter($parameterName, '%ROLE_ADMIN%');
        }
    }
}
