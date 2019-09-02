<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Plan;

use Exception;

abstract class ConstraintExceeded extends Exception
{
    abstract public function getTranslatorId(): string;

    abstract public function getTranslationParams(): array;
}
