<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Email;

use Assert\Assertion;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\Webhosting\Space\Space;

/**
 * @ORM\Entity
 * @ORM\Table(name="mailbox", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uk_mailbox_address_name", columns={"address", "domain_name"})
 * })
 */
class Mailbox implements \Stringable
{
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_mailbox_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public MailboxId $id;

    /**
     * @ORM\ManyToOne(targetEntity=Space::class)
     * @ORM\JoinColumn(name="space_id", onDelete="RESTRICT")
     */
    public Space $space;

    /**
     * @ORM\ManyToOne(targetEntity=DomainName::class)
     * @ORM\JoinColumn(name="domain_name", onDelete="RESTRICT")
     */
    public DomainName $domainName;

    /**
     * @ORM\Column(type="string")
     */
    public string $address;

    /**
     * @ORM\Column(type="byte_size")
     */
    public ByteSize $size;

    /**
     * Password (in hashed format).
     *
     * @ORM\Column(type="text", name="auth_password")
     */
    public string $password;

    /**
     * @ORM\Column(type="boolean")
     */
    public bool $active = true;

    public function __construct(MailboxId $id, Space $space, string $address, DomainName $domainName, ByteSize $size, string $password)
    {
        $this->id = $id;
        $this->space = $space;
        $this->size = $size;
        $this->password = $password;

        $this->setAddress($address, $domainName);
    }

    public function setAddress(string $address, DomainName $domainName = null): void
    {
        $domainName ??= $this->domainName;
        $emailAddress = new EmailAddress($address . '@' . $domainName->namePair->toString());
        $emailAddress->validate();

        Assertion::false($emailAddress->isPattern, 'Mailbox Address cannot be a pattern', 'address');
        Assertion::same($emailAddress->label, '', 'Label is not allowed for a Mailbox address', 'address');
        Assertion::same($domainName->space, $this->space, 'DomainName must be part of the same Space', 'address');

        $this->address = $emailAddress->local;
        $this->domainName = $domainName;
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

    public function deActivate(): void
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
