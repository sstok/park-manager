<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\DomainName\TLS\Certificate;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="sub_domain",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="sub_domain_uniq", columns={"host", "name_part"}),
 *     }
 * )
 */
class SubDomain
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_sub_domain_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public SubDomainNameId $id;

    /**
     * READ-ONLY.
     *
     * @ORM\ManyToOne(targetEntity=DomainName::class)
     * @ORM\JoinColumn(name="host", nullable=false)
     */
    public DomainName $host;

    /**
     * @ORM\Column(type="text", name="name_part")
     */
    public string $name;

    /**
     * Home-directory (relative path of park group, either `/site1`).
     *
     * @ORM\Column(type="text")
     */
    public string $homeDir;

    /**
     * @ORM\Column(type="boolean")
     */
    public bool $active = true;

    /**
     * Configuration for the web server, normalized.
     *
     * @ORM\Column(type="json")
     */
    public array $config = [];

    /**
     * @ORM\ManyToOne(targetEntity=Certificate::class)
     * @ORM\JoinColumn(name="tls_cert", referencedColumnName="hash", nullable=true)
     */
    public ?Certificate $tlsCert = null;

    public function __construct(SubDomainNameId $id, DomainName $host, string $name, string $homeDir, array $config)
    {
        $this->id = $id;
        $this->host = $host;
        $this->name = $name;
        $this->homeDir = $homeDir;
        $this->config = $config;
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

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
