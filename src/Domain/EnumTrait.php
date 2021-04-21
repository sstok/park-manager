<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Error;
use ReflectionClass;
use ReflectionClassConstant;
use TypeError;

/**
 * EnumTrait serves a temporary replacement till (Backed) Enums are supported in PHP 8.1.
 *
 * Note:
 * - Use equals() instead of object comparisons due to serialization limitations.
 * - Use get() to get an instance.
 */
trait EnumTrait
{
    private static ?bool $isInt = null;
    /** @var array<string, static>|null */
    private static ?array $casesByName = null;
    /** @var array<string | int, static>|null */
    private static ?array $casesByValue = null;

    public string $name;
    public string | int $value;

    private function __construct(string $name, string | int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function equals(?self $instance): bool
    {
        if ($instance === null) {
            return false;
        }

        return $instance->name === $this->name;
    }

    public static function equalsTo(?self $instance, ?self $other): bool
    {
        if ($instance === null || $other === null) {
            return false;
        }

        return $instance->name === $other->name;
    }

    public static function equalsToAny(?self $instance, ?self ...$other): bool
    {
        if ($instance === null) {
            return false;
        }

        foreach ($other as $value) {
            if ($value !== null && $instance->name === $value->name) {
                return true;
            }
        }

        return false;
    }

    public static function get(string $name): static
    {
        static::initCases();

        $nameNorm = \mb_strtolower($name);

        if (! isset(static::$casesByName[$nameNorm])) {
            throw new Error(\sprintf('Enum case %s is not defined', $name));
        }

        return static::$casesByName[$nameNorm];
    }

    private static function initCases(): void
    {
        if (static::$casesByName !== null) {
            return;
        }

        static::$casesByName = [];
        static::$casesByValue = [];

        /** @psalm-suppress ImpureMethodCall this reflection API usage has no side-effects here */
        $reflection = new ReflectionClass(static::class);

        /** @psalm-suppress ImpureMethodCall this reflection API usage has no side-effects here */
        foreach ($reflection->getConstants(ReflectionClassConstant::IS_PUBLIC) as $name => $value) {
            // Names are case-insensitive according to the original RFC, but constants are not.
            // For technical reasons we still use the original name with the instance.
            $nameNorm = \mb_strtolower($name);

            if (isset(static::$casesByName[$nameNorm])) {
                throw new Error(
                    \sprintf('Cannot redeclare %s::%s', static::class, static::$casesByName[$nameNorm]->name)
                );
            }

            if (isset(static::$casesByValue[$value])) {
                throw new Error(
                    \sprintf(
                        'Duplicate value in enum %s for cases %s and %s',
                        static::class,
                        static::$casesByValue[$value]->name,
                        $name
                    )
                );
            }

            static::$casesByName[$nameNorm] = new static($name, $value);
            static::$casesByValue[$value] = static::$casesByName[$nameNorm];

            if (static::$isInt === null) {
                static::$isInt = \is_int($value);

                continue;
            }

            if (static::$isInt && ! \is_int($value)) {
                throw new Error('Enum case type string does not match enum scalar type int');
            }

            if (! static::$isInt && ! \is_string($value)) {
                throw new Error('Enum case type int does not match enum scalar type string');
            }
        }
    }

    /**
     * @return array<string, static>
     */
    public static function cases(): array
    {
        static::initCases();

        return \array_values(static::$casesByName);
    }

    public static function from(string | int $value): static
    {
        static::initCases();
        static::assertExpectedType($value, 'from');

        if (! isset(static::$casesByValue[$value])) {
            throw new Error(\sprintf('Unable to find matching case for value "%s"', $value));
        }

        return static::$casesByValue[$value];
    }

    private static function assertExpectedType(int | string $value, string $method): void
    {
        if (static::$isInt && ! \is_int($value)) {
            throw new TypeError(
                \sprintf('%s::%s(): Argument #1 ($value) must be of type int, string given', static::class, $method)
            );
        }

        if (! static::$isInt && ! \is_string($value)) {
            throw new TypeError(
                \sprintf('%s::%s(): Argument #1 ($value) must be of type string, int given', static::class, $method)
            );
        }
    }

    public static function tryFrom(string | int $value): ?static
    {
        static::initCases();
        static::assertExpectedType($value, 'tryFrom');

        return static::$casesByValue[$value] ?? null;
    }
}
