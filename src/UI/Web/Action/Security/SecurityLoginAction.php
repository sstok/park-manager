<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityLoginAction extends AbstractController
{
    #[Route(path: '/login', name: 'park_manager.security_login', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $authenticationUtils = $this->container->get('security.authentication_utils');

        return $this->render('security/login.html.twig', [
            'route' => 'park_manager.security_login',
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            'security.authentication_utils' => AuthenticationUtils::class,
        ];
    }
}
