<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use InvalidArgumentException;
use ParkManager\Domain\Translation\TranslatableMessage;

abstract class Violation extends InvalidArgumentException
{
    abstract public function getTranslatorId(): string;

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return [];
    }

    public function toTranslatableMessage(): TranslatableMessage
    {
        return new TranslatableMessage($this->getTranslatorId(), $this->getParameters(), 'validators');
    }
}
