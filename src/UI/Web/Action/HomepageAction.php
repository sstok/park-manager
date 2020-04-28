<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action;

use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Routing\Annotation\Route;

final class HomepageAction
{
    /**
     * @Route(
     *     path="/",
     *     name="park_manager.user.home",
     *     methods={"GET"}
     * )
     */
    public function __invoke(): TwigResponse
    {
        return new TwigResponse('index.html.twig');
    }
}
