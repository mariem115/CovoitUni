<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        $roles = $token->getRoleNames();

        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin'));
        }

        if ($user instanceof User && $request->isMethod('POST') && $request->request->has('_role')) {
            $intended = $request->request->getString('_role', 'passager');
            $newRole = 'conducteur' === $intended ? 'ROLE_CONDUCTEUR' : 'ROLE_PASSAGER';
            $user->setRoles(['ROLE_USER', $newRole]);
            $this->entityManager->flush();

            if ('conducteur' === $intended) {
                return new RedirectResponse($this->urlGenerator->generate('app_profile_trips'));
            }

            return new RedirectResponse($this->urlGenerator->generate('app_trip_index'));
        }

        if ($user instanceof User) {
            $dbRoles = $user->getRoles();
            if (\in_array('ROLE_CONDUCTEUR', $dbRoles, true)) {
                return new RedirectResponse($this->urlGenerator->generate('app_profile_trips'));
            }
            if (\in_array('ROLE_PASSAGER', $dbRoles, true)) {
                return new RedirectResponse($this->urlGenerator->generate('app_trip_index'));
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile_my'));
    }
}
