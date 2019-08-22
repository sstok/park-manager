<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Action;

use ParkManager\Bundle\CoreBundle\Http\Response\TwigResponse;

final class HomepageAction
{
    public function __invoke(): TwigResponse
    {
        return new TwigResponse('@ParkManagerCore/index.html.twig');
    }
}