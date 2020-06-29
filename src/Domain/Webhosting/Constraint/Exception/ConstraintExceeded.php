<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint\Exception;

use Exception;
use ParkManager\Domain\Exception\TranslatableException;

final class ConstraintExceeded extends Exception implements TranslatableException
{
    private string $transId;
    private array $transArgs;

    private function __construct(string $message, array $transArgs = [])
    {
        $message = 'space_constraint_exceeded.' . $message;

        parent::__construct($message);

        $this->transId = $message;
        $this->transArgs = $transArgs;
    }

    public function getTranslatorId(): string
    {
        return $this->transId;
    }

    public function getTranslationArgs(): array
    {
        return $this->transArgs;
    }
}
