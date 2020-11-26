<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
final class DomainNamePair
{
    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="string")
     */
    public string $name;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="string")
     */
    public string $tld;

    private string $idnValue;

    public function __construct(string $name, string $tld)
    {
        $this->name = $name;
        $this->tld = $tld;
    }

    public function toString(): string
    {
        $this->idnValue ??= (string) \idn_to_utf8($this->name . '.' . $this->tld, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return $this->idnValue;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->tld === $other->tld;
    }
}
