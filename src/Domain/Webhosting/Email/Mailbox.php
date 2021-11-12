<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Email;

use Assert\Assertion;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\Webhosting\Space\Space;
use Stringable;

#[Entity]
#[Table(name: 'mailbox')]
#[UniqueConstraint(name: 'uk_mailbox_address_name', columns: ['address', 'domain_name'])]
class Mailbox implements Stringable
{
    use TimestampableTrait;

    #[Column(type: 'string')]
    public string $address;

    #[Column(type: 'boolean')]
    public bool $active = true;

    public bool $addressChanged = false;

    /**
     * @param string $password password (in hashed format)
     */
    public function __construct(
        #[Id]
        #[Column(type: 'park_manager_webhosting_mailbox_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public MailboxId $id,

        #[ManyToOne(targetEntity: Space::class)]
        #[JoinColumn(name: 'space_id', onDelete: 'RESTRICT')]
        public Space $space,

        string $address,

        #[ManyToOne(targetEntity: DomainName::class)]
        #[JoinColumn(name: 'domain_name', onDelete: 'RESTRICT')]
        public DomainName $domainName,

        #[Column(type: 'byte_size')]
        public ByteSize $size,

        #[Column(name: 'auth_password', type: 'text')]
        public string $password
    ) {
        $this->setAddress($address);
        $this->addressChanged = false;
    }

    public function setAddress(string $address, ?DomainName $domainName = null): void
    {
        $domainName ??= $this->domainName;
        $emailAddress = new EmailAddress($address . '@' . $domainName->namePair->toString());
        $emailAddress->validate();

        Assertion::false($emailAddress->isPattern, 'Mailbox Address cannot be a pattern', 'address');
        Assertion::same($emailAddress->label, '', 'Label is not allowed for a Mailbox address', 'address');
        Assertion::same($domainName->space, $this->space, 'DomainName must be part of the same Space', 'address');

        $this->address = $emailAddress->local;
        $this->domainName = $domainName;
        $this->addressChanged = true;
    }

    public function changePassword(string $password): void
    {
        $this->password = $password;
    }

    public function resize(ByteSize $size): void
    {
        $this->size = $size;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function toString(): string
    {
        return $this->address . '@' . $this->domainName->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
