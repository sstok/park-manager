<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Ftp;

use Assert\Assertion;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Lifthill\Component\Common\Domain\Attribute\Entity as DomainEntity;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Space\Space;

#[Entity]
#[Table(name: 'ftp_user')]
#[UniqueConstraint(name: 'ftp_username', columns: ['space_domain_name_id', 'username'])]
#[DomainEntity]
class FtpUser
{
    #[Column(type: 'boolean')]
    public bool $enabled = true;

    public function __construct(
        #[Id]
        #[Column(type: 'park_manager_ftp_user_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public FtpUserId $id,

        #[ManyToOne(targetEntity: Space::class)]
        #[JoinColumn(name: 'space', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
        public Space $space,

        #[Column(name: 'username', type: 'text')]
        public string $username,

        #[Column(name: 'passwd', type: 'text')]
        public string $password,

        #[ManyToOne(targetEntity: DomainName::class)]
        #[JoinColumn(name: 'space_domain_name_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
        public DomainName $domainName,

        /**
         * Home-directory. Optional if not set uses the webhosting root-directory.
         */
        #[Column(type: 'text', nullable: true)]
        public ?string $homeDir = null,
    ) {
        $this->guardLinkedDomainName($domainName);
    }

    private function guardLinkedDomainName(DomainName $domainName): void
    {
        Assertion::same(
            $domainName->space,
            $this->space,
            'Assigned DomainName must be part of the same Space',
            'username'
        );
    }

    public function changeUsername(string $username, DomainName $domainName = null): void
    {
        $domainName ??= $this->domainName;

        $this->guardLinkedDomainName($domainName);

        $this->username = $username;
        $this->domainName = $domainName;
    }

    public function changePassword(string $password): void
    {
        $this->password = $password;
    }

    public function changeHomeDir(?string $homeDir): void
    {
        $this->homeDir = $homeDir;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
