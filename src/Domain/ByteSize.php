<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

final class ByteSize
{
    public int $value;

    public function __construct(int $size, string $unit)
    {
        $target = $size;

        switch (\mb_strtolower($unit)) {
            case 'b':
            case 'byte':
                // no-op
                break;

            case 'inf':
                $target = -1;

                break;

            case 'k':
            case 'kb':
                $target *= 1000;

                break;

            case 'ki':
            case 'kib':
                $target *= 1024;

                break;

            case 'm':
            case 'mb':
                $target *= 1000000;

                break;

            case 'mi':
            case 'mib':
                $target *= 1024 * 1024;

                break;

            case 'g':
            case 'gb':
                $target *= 1000000000;

                break;

            case 'gi':
            case 'gib':
                $target *= 1024 * 1024 * 1024;

                break;

            default:
                throw new \InvalidArgumentException(\sprintf('Unknown or unsupported unit "%s".', $unit));
        }

        $this->value = $target;
    }

    public static function inf(): self
    {
        return new self(-1, 'inf');
    }

    public function format(): string
    {
        if ($this->value >= 1024 * 1024 * 1024) {
            return \sprintf('%.2f GiB', $this->value / 1024 / 1024 / 1024);
        }

        if ($this->value >= 1024 * 1024) {
            return \sprintf('%.2f MiB', $this->value / 1024 / 1024);
        }

        if ($this->value >= 1024) {
            return \sprintf('%.2f KiB', $this->value / 1024);
        }

        return \sprintf('%d B', $this->value);
    }

    public function __debugInfo()
    {
        return [
            'value' => $this->value,
            '_formatted' => $this->format(),
        ];
    }

    public function getUnit(): string
    {
        if ($this->value >= 1024 * 1024 * 1024) {
            return 'GiB';
        }

        if ($this->value >= 1024 * 1024) {
            return 'MiB';
        }

        if ($this->value >= 1024) {
            return 'KiB';
        }

        return 'B';
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->value === $other->value;
    }

    public function isInf(): bool
    {
        return $this->value === -1;
    }

    public function lessThan(self $other): bool
    {
        if ($this->isInf()) {
            return false;
        }

        if ($other->isInf()) {
            return true;
        }

        return $this->value < $other->value;
    }

    public function greaterThan(self $other): bool
    {
        if ($this->isInf()) {
            return true;
        }

        if ($other->isInf()) {
            return false;
        }

        return $this->value > $other->value;
    }

    public function lessThanOrEqualTo(self $other): bool
    {
        return $this->lessThan($other) || $this->equals($other);
    }

    public function greaterThanOrEqualTo(self $other): bool
    {
        return $this->greaterThan($other) || $this->equals($other);
    }

    public function decrease(self $other, bool $roundToZero = true): self
    {
        if ($other->isInf()) {
            return $this;
        }

        if ($this->isInf()) {
            return $other;
        }

        $size = $this->value - $other->value;

        if ($size < 0 && $roundToZero) {
            $size = 0;
        }

        return new self($size, 'b');
    }

    public function increase(self $other): self
    {
        if ($other->isInf()) {
            return $other;
        }

        if ($this->isInf()) {
            return $this;
        }

        return new self($this->value + $other->value, 'b');
    }
}
