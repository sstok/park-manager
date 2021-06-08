<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain;

use Assert\Assertion;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\SubDomain\TLS\Certificate;

#[Entity]
#[Table(name: 'sub_domain')]
#[UniqueConstraint(name: 'sub_domain_uniq', columns: ['host_id', 'name_part'])]

class SubDomain
{
    #[ManyToOne(targetEntity: Space::class)]
    #[JoinColumn(name: 'space', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    public Space $space;

    #[Column(type: 'boolean')]
    public bool $active = true;

    #[ManyToOne(targetEntity: Certificate::class)]
    #[JoinColumn(name: 'tls_cert', referencedColumnName: 'hash', nullable: true)]
    public ?Certificate $tlsCert = null;

    /**
     * @param array<string, string|int|array> $config configuration for the web server, normalized
     */
    public function __construct(
        #[Id]
        #[Column(type: 'park_manager_sub_domain_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public SubDomainNameId $id,

        #[ManyToOne(targetEntity: DomainName::class)]
        #[JoinColumn(name: 'host_id', nullable: false, onDelete: 'RESTRICT')]
        public DomainName $host,

        #[Column(name: 'name_part', type: 'text')]
        public string $name,

        /**
         * Home-directory (relative path of park group, either `/site1`).
         */
        #[Column(type: 'text')]
        public string $homeDir,

        #[Column(type: 'json')]
        public array $config = []
    ) {
        Assertion::notNull($host->space, 'DomainName must be assigned to a Space for usage with a SubDomain.', 'host');

        $this->space = $host->space;
    }

    public function assignTlsConfiguration(?Certificate $cert): void
    {
        $this->tlsCert = $cert;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setHomeDir(string $homeDir): void
    {
        $this->homeDir = $homeDir;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deActivate(): void
    {
        $this->active = false;
    }

    /**
     * @param array<string, string|int|array> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
