<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Action\Admin;

use ParkManager\Bundle\CoreBundle\Http\Response\TwigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityLoginAction extends AbstractController
{
    public function __invoke(Request $request): TwigResponse
    {
        // Adding an ArgumentResolver for this single service would be overkill.
        $authenticationUtils = $this->get('security.authentication_utils');

        return new TwigResponse('@ParkManagerCore/admin/security/login.html.twig', [
            'route' => 'park_manager.admin.security_login',
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return [
            'security.authentication_utils' => AuthenticationUtils::class,
        ];
    }
}
