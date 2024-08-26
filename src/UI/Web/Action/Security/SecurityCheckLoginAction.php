<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use Symfony\Component\Routing\Attribute\Route;

/**
 * @codeCoverageIgnore
 */
final class SecurityCheckLoginAction
{
    #[Route(path: '/login_check', name: 'park_manager.security_check_login', methods: ['POST'])]
    public function __invoke(): void
    {
        throw new \RuntimeException('Check your security firewall configuration. /login_check should not be accessed directly.');
    }
}
