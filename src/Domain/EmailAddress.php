<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use Symfony\Component\Mime\Address;
use const IDNA_DEFAULT;
use const INTL_IDNA_VARIANT_UTS46;
use const MB_CASE_LOWER;

/**
 * @ORM\Embeddable
 */
final class EmailAddress
{
    /**
     * READ-ONLY.
     *
     * Length by official standard.
     *
     * @ORM\Column(type="string", length=254, nullable=false)
     */
    public string $address;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="string", length=254, nullable=false)
     */
    public string $canonical;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     */
    public ?string $name = null;

    /**
     * READ-ONLY.
     *
     * Unmapped. Label is already part of the original value and unimportant.
     */
    public string $label = '';

    /**
     * READ-ONLY.
     */
    public bool $isPattern = false;

    /**
     * READ-ONLY.
     *
     * Unmapped. Already part of the original value.
     */
    public string $local = '';

    /**
     * READ-ONLY.
     *
     * Unmapped. Already part of the original value.
     */
    public string $domain = '';

    public function __construct(string $address, ?string $name = null)
    {
        $this->address = $address;
        $this->canonical = $this->canonicalize($address, $this->local, $this->domain, $this->label);
        $this->name = $name;

        if (\mb_strpos($address, '*') !== false) {
            $this->validatePattern();

            $this->isPattern = true;
        }
    }

    public function validatePattern(): void
    {
        if (\mb_substr_count($this->address, '*') > 1) {
            throw MalformedEmailAddress::patternMultipleWildcards($this->address);
        }

        if (\mb_strrpos($this->label, '*') !== false) {
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
        new Address(\str_replace('*', 't', $this->address), '');
    }

    public function toMimeAddress(): Address
    {
        return new Address($this->address, $this->name ?? '');
    }

    private function canonicalize(string $address, string &$local, string &$domain, string &$label): string
    {
        $atPos = \mb_strrpos($address, '@', 0, 'UTF-8');

        if ($atPos === false) {
            throw MalformedEmailAddress::missingAtSign($address);
        }

        // The label is only used for information, but still points to the same
        // inbox. Keeping this would make it possible to reuse the same address
        // for the same user, leading to all kinds of trouble.
        $local = \mb_substr($address, 0, $atPos, 'UTF-8');
        $local = $this->extractLabel($local, $label);

        $domain = \mb_substr($address, $atPos + 1);
        $domain = (string) \idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnaInfo);

        if ($idnaInfo['errors'] !== 0) {
            throw MalformedEmailAddress::idnError($address, $idnaInfo['errors']);
        }

        // While not officially required (as the local part is case-sensitive) it's generally
        // better to lowercase the local part also to prevent spoofing and typo's
        // (and nobody uses case-sensitive addresses ¯\_(ツ)_/¯ )

        $local = \mb_convert_case($local, MB_CASE_LOWER, 'UTF-8');
        $domain = \mb_convert_case($domain, MB_CASE_LOWER, 'UTF-8');

        return $local . '@' . $domain;
    }

    private function extractLabel(string $local, string &$label): string
    {
        $labelPos = \mb_strrpos($local, '+', 0, 'UTF-8');

        if ($labelPos !== false) {
            $label = \mb_substr($local, ++$labelPos, $labelEnd = \mb_strlen($local, 'UTF-8') - $labelPos, 'UTF-8');
            $local = \mb_substr($local, 0, $labelEnd - 1, 'UTF-8');
        }

        return $local;
    }
}
