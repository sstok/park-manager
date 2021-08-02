<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

use Symfony\Contracts\Translation\TranslatableInterface;
use Throwable;

/**
 * Implement this interface to make the exception user translatable.
 *
 * Use the exception code as HTTP status code.
 */
interface DomainError extends Throwable
{
    /**
     * Returns the Translation-id in the validators+intl-icu domain.
     */
    public function getTranslatorMsg(): string | TranslatableInterface;
}
