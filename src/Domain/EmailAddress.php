<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use const IDNA_DEFAULT;
use const INTL_IDNA_VARIANT_UTS46;
use const MB_CASE_LOWER;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use Stringable;
use Symfony\Component\Mime\Address;
use Symfony\Component\String\UnicodeString;

#[Embeddable]
final class EmailAddress implements Stringable
{
    #[Column(type: 'string', length: 254, nullable: false)]
    public string $canonical;

    /**
     * Unmapped. Label is already part of the original value and unimportant.
     */
    public string $label = '';

    public bool $isPattern = false;

    /**
     * Unmapped. Already part of the original value.
     */
    public string $local = '';

    /**
     * Unmapped. Already part of the original value.
     */
    public string $domain = '';

    public function __construct(
        /**
         * Length by official standard.
         */
        #[Column(type: 'string', length: 254, nullable: false)]
        public string $address,

        #[Column(type: 'string', length: 254, nullable: true)]
        public ?string $name = null
    ) {
        $this->canonical = $this->canonicalize($address, $this->local, $this->domain, $this->label);

        if (str_contains($address, '*')) {
            $this->validatePattern();

            $this->isPattern = true;
        }
    }

    private function validatePattern(): void
    {
        if (mb_substr_count($this->address, '*') > 1) {
            throw MalformedEmailAddress::patternMultipleWildcards($this->address);
        }

        if (mb_strrpos($this->label, '*') !== false) {
            throw MalformedEmailAddress::patternWildcardInLabel($this->address);
        }
    }

    public function toString(): string
    {
        return $this->address;
    }

    public function __toString(): string
    {
        return $this->address;
    }

    public function validate(): void
    {
        new Address($this->address, '');
    }

    public function toMimeAddress(): Address
    {
        return new Address($this->address, $this->name ?? '');
    }

    private function canonicalize(string $address, string &$local, string &$domain, string &$label): string
    {
        $atPos = mb_strrpos($address, '@', 0, 'UTF-8');

        if ($atPos === false) {
            throw MalformedEmailAddress::missingAtSign($address);
        }

        // The label is only used for information, but still points to the same
        // inbox. Keeping this would make it possible to reuse the same address
        // for the same user, leading to all kinds of trouble.
        $local = mb_substr($address, 0, $atPos, 'UTF-8');
        $local = $this->extractLabel($local, $label);

        $domain = mb_substr($address, $atPos + 1);

        if (trim($domain) === '') {
            throw MalformedEmailAddress::idnError($address, \IDNA_ERROR_EMPTY_LABEL);
        }

        $domain = (string) idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnaInfo);

        if ($idnaInfo['errors'] !== 0) {
            throw MalformedEmailAddress::idnError($address, $idnaInfo['errors']);
        }

        // While not officially required (as the local part is case-sensitive) it's generally
        // better to lowercase the local part also to prevent spoofing and typo's
        // (and nobody uses case-sensitive addresses ¯\_(ツ)_/¯ )

        $local = mb_convert_case($local, MB_CASE_LOWER, 'UTF-8');
        $domain = mb_convert_case($domain, MB_CASE_LOWER, 'UTF-8');

        return $local . '@' . $domain;
    }

    private function extractLabel(string $local, string &$label): string
    {
        $labelPos = mb_strrpos($local, '+', 0, 'UTF-8');

        if ($labelPos !== false) {
            $label = mb_substr($local, $labelPos + 1, mb_strlen($local, 'UTF-8'), 'UTF-8');
            $local = mb_substr($local, 0, $labelPos, 'UTF-8');
        }

        return $local;
    }

    public function truncate(int $length = 27, string $ellipsis = '...'): string
    {
        if ($length >= mb_strlen($this->address)) {
            return $this->address;
        }

        $length -= (int) floor(mb_strlen($ellipsis) / 2);

        $text = new UnicodeString($this->address);
        $atSign = $text->indexOfLast('@');

        return $text->slice(0, $atSign)->truncate($length, $ellipsis) . $text->slice($atSign)->truncate($length, $ellipsis);
    }

    public function equals(self $other): bool
    {
        if ($other === $this) {
            return true;
        }

        return $this->address === $other->address && $this->name === $other->name;
    }
}
