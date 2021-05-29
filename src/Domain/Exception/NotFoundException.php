<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Extend this class for not-found exception classes,
 * making the exception automatically translatable.
 */
abstract class NotFoundException extends InvalidArgumentException implements TranslatableException
{
    /** @var array<string, DateTimeInterface|float|int|string|Stringable|TranslatableInterface|null> */
    protected array $translationArgs = [];

    /**
     * @param array<string, DateTimeInterface|float|int|string|Stringable|TranslatableInterface|null> $translationArgs
     */
    public function __construct(string $message = 'Not found', array $translationArgs = [])
    {
        parent::__construct($message, 404);
        $this->translationArgs = $translationArgs;
    }

    public function getTranslatorId(): string
    {
        return 'Not found';
    }

    public function getTranslationArgs(): array
    {
        return $this->translationArgs;
    }
}
