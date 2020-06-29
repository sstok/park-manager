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
            return \sprintf('%.1f GiB', $this->value / 1024 / 1024 / 1024);
        }

        if ($this->value >= 1024 * 1024) {
            return \sprintf('%.1f MiB', $this->value / 1024 / 1024);
        }

        if ($this->value >= 1024) {
            return \sprintf('%d KiB', $this->value / 1024);
        }

        return \sprintf('%d B', $this->value);
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
