<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use RuntimeException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @codeCoverageIgnore
 */
final class SecurityLogoutAction
{
    /**
     * @Route(
     *     path="/logout",
     *     name="park_manager.security_logout",
     *     methods={"GET"}
     * )
     */
    public function __invoke(): void
    {
        throw new RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
