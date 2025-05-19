<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\SubDomain;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;

abstract class SubDomainCommand
{
    public ?string $certificate = null;
    public ?HiddenString $privateKey = null;
    /** @var array<string, string> */
    public array $caList = [];

    /**
     * @param array<string, mixed> $config
     */
    final public function __construct(
        public SubDomainNameId $id,
        public DomainNameId $domainNameId,
        public string $name,
        public string $homeDir = '/',
        public array $config = []
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function with(string $id, string $domainId, string $name, string $homeDir = '/', array $config = []): static
    {
        return new static(SubDomainNameId::fromString($id), DomainNameId::fromString($domainId), $name, $homeDir, $config);
    }

    /**
     * @param array<string, string> $caList [user-provided CA-name => X509 contents]
     *
     * @return $this
     */
    public function andTLSInformation(string $certificate, HiddenString $privateKey, array $caList = []): static
    {
        $this->certificate = $certificate;
        $this->privateKey = $privateKey;
        $this->caList = $caList;

        return $this;
    }
}
