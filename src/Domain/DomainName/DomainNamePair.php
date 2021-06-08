<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Stringable;
use Symfony\Component\String\UnicodeString;

#[Embeddable]
final class DomainNamePair implements Stringable
{
    private string $idnValue;

    public function __construct(
        #[Column(type: 'string')]
        public string $name,

        #[Column(type: 'string')]
        public string $tld
    ) {
    }

    public function toString(): string
    {
        $this->idnValue ??= (string) idn_to_utf8($this->name . '.' . $this->tld, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);

        return $this->idnValue;
    }

    public function toTruncatedString(int $length = 27, string $ellipsis = '[...]'): string
    {
        $address = $this->toString();

        if ($length >= mb_strlen($address)) {
            return $address;
        }

        $text = new UnicodeString($address);
        $separator = $text->indexOf('.');

        $name = $text->slice(0, $separator);
        $tld = $text->slice($separator);

        $length -= $tld->length(); // TLD is never reduced

        return $name->truncate($length, $ellipsis) . $tld;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->tld === $other->tld;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
