<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain\TLS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="host_tls_ca")
 */
class CA
{
    use x509Data {
        __construct as construct;
    }

    /** @var array<int, CA>|null */
    private ?array $tree;

    /**
     * @param array<string, mixed> $rawFields
     */
    public function __construct(string $contents, array $rawFields, ?self $ca = null)
    {
        $this->construct($contents, $rawFields, $ca);
    }

    /**
     * @return array<string, string>
     */
    public function getSubject(): array
    {
        return $this->rawFields['subject'];
    }

    public function isRoot(): bool
    {
        return $this->ca === null;
    }

    public function getParent(): ?self
    {
        return $this->ca;
    }

    /**
     * @return array<int, CA>
     */
    public function toTree(): array
    {
        if (! isset($this->tree)) {
            $list = [$this];
            $ca = $this;

            while ($ca = $ca->ca) {
                $list[] = $ca;
            }

            $this->tree = array_reverse($list);
        }

        return $this->tree;
    }
}
