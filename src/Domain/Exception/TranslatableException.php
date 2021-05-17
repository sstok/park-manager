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
interface TranslatableException extends Throwable
{
    /**
     * Returns the Translation-id in the messages+intl-icu domain.
     */
    public function getTranslatorId(): string;

    /**
     * Returns the arguments for the translator-id (if any).
     *
     * The values of arguments can be translated separately
     * (passing the value of an argument to translator) by
     * prefixing their key with `@`, like `@status`
     * or using a `TranslatableInterface` instance as value.
     *
     * @return array<string, \DateTimeInterface|TranslatableInterface|float|int|string|null>
     */
    public function getTranslationArgs(): array;
}
