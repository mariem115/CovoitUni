<?php

namespace App\Controller\Admin;

use App\Admin\Filter\UserStoredRolesFilter;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setSearchFields(['firstName', 'lastName', 'email', 'university']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified'))
            ->add(UserStoredRolesFilter::new('roles'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('firstName');
        yield TextField::new('lastName');
        yield EmailField::new('email');
        yield TextField::new('university');
        yield ArrayField::new('roles');
        yield DateTimeField::new('createdAt');
        yield BooleanField::new('isVerified');
    }

    public function configureActions(Actions $actions): Actions
    {
        $banUser = Action::new('banUser', 'Ban User', 'bi bi-slash-circle')
            ->linkToCrudAction('banUser')
            ->asDangerAction()
            ->renderAsForm()
            ->displayIf(function (?User $user) {
                if (null === $user || null === $user->getId()) {
                    return false;
                }
                $current = $this->getUser();

                return $current instanceof User && $current->getId() !== $user->getId();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $banUser)
            ->add(Crud::PAGE_DETAIL, $banUser);
    }

    #[AdminRoute(path: '/{entityId}/ban-user', name: 'ban_user', options: ['methods' => ['POST']])]
    public function banUser(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGeneratorInterface $adminUrlGenerator): Response
    {
        /** @var User|null $user */
        $user = $context->getEntity()->getInstance();
        if (!$user instanceof User) {
            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $current = $this->getUser();
        if ($current instanceof User && $current->getId() === $user->getId()) {
            $this->addFlash('danger', 'You cannot ban your own account.');

            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($user->getId())->generateUrl());
        }

        $user->setRoles([]);
        $user->setIsVerified(false);
        $entityManager->flush();

        $this->addFlash('success', sprintf('User "%s" was banned.', $user->getEmail() ?? ''));

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }
}
