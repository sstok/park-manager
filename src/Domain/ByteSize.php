<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use ParkManager\Domain\Exception\InvalidByteSize;
use Stringable;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ByteSize implements Stringable, TranslatableInterface
{
    public int $value;

    public function __construct(mixed $size, string $unit)
    {
        // While the type is mixed, this is only for user input, we only accept integer and float.
        if (! \is_int($size) && ! \is_float($size)) {
            throw new InvalidByteSize('Expected the size to be an integer or float.');
        }

        $target = $size;

        switch (mb_strtolower($unit)) {
            case 'b':
            case 'byte':
                if (! ctype_digit(ltrim((string) $size, '-'))) {
                    throw new InvalidByteSize('The unit "byte" must be a whole number without a fraction.');
                }

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
                throw new InvalidByteSize(sprintf('Unknown or unsupported unit "%s".', $unit));
        }

        $this->value = (int) $target;
    }

    public static function fromString(string $input): self
    {
        if ($input === '-1' || mb_strtolower($input) === 'inf') {
            return self::inf();
        }

        if (! preg_match('{^(?P<size>\d+(?:\.\d{0,2})?)\h*(?P<unit>[a-z]{1,3})$}i', $input, $matches)) {
            throw new InvalidByteSize(sprintf('Invalid ByteSize format provided "%s". Expected value and unit as either "12 Mib" or "12 MB". Or "inf" otherwise.', $input));
        }

        return new self((float) $matches['size'], $matches['unit']);
    }

    public static function inf(): self
    {
        return new self(-1, 'inf');
    }

    public function format(): string
    {
        if ($this->isInf()) {
            return 'inf';
        }

        if ($this->value >= 1024 * 1024 * 1024) {
            return sprintf('%.2f GiB', round($this->value / 1024 / 1024 / 1024, 2));
        }

        if ($this->value >= 1024 * 1024) {
            return sprintf('%.2f MiB', round($this->value / 1024 / 1024, 2));
        }

        if ($this->value >= 1024) {
            return sprintf('%.2f KiB', round($this->value / 1024, 2));
        }

        return sprintf('%d B', $this->value);
    }

    /**
     * @return array{value: int, _formatted: string}
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            '_formatted' => $this->format(),
        ];
    }

    /**
     * Returns the size in a normalized format to their nearest Ibi-byte unit-size.
     *
     * Caution: Float values are rounded too two digits and might loose some precession.
     */
    public function getNormSize(): int | float
    {
        if ($this->value >= 1024 * 1024 * 1024) {
            return round($this->value / 1024 / 1024 / 1024, 2);
        }

        if ($this->value >= 1024 * 1024) {
            return round($this->value / 1024 / 1024, 2);
        }

        if ($this->value >= 1024) {
            return round($this->value / 1024, 2);
        }

        return $this->value;
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

    /**
     * Returns the remaining percentage of the original
     * (this) value and the current.
     *
     * If original is 100 GiB and current is 60 GiB
     * this leaves around 40% of remaining space.
     *
     * Returns 100 if either of the two values is Inf.
     *
     * Caution: Do not switch the values to invert the result.
     */
    public function getDiffRemainder(self $current): int | float
    {
        if ($this->isInf() || $current->isInf()) {
            return 100;
        }

        $original = $this->value;
        $diff = abs($current->value - $original);

        return ($diff / $original) * 100;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        if ($this->isInf()) {
            return $translator->trans('byte_size.inf', domain: 'messages', locale: $locale);
        }

        $unit = mb_strtolower($this->getUnit());

        if ($unit === 'b') {
            $unit = 'byte';
        }

        return $translator->trans(
            'byte_size.format',
            [
                'value' => $this->getNormSize(),
                'unit' => $translator->trans('byte_size.' . $unit, domain: 'messages', locale: $locale),
            ],
            'messages',
            $locale
        );
    }
}
