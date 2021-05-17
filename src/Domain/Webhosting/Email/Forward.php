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
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Space\Space;

/**
 * @ORM\Entity
 * @ORM\Table(name="mail_forward", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uk_mail_forward_address_name", columns={"address", "domain_name"})
 * })
 */
class Forward
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_mail_forward_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public ForwardId $id;

    /**
     * @ORM\ManyToOne(targetEntity=Space::class)
     * @ORM\JoinColumn(name="space_id", onDelete="RESTRICT")
     */
    public Space $space;

    /**
     * @ORM\Column(type="text")
     */
    public string $address;

    /**
     * @ORM\ManyToOne(targetEntity=DomainName::class)
     * @ORM\JoinColumn(name="domain_name", onDelete="RESTRICT")
     */
    public DomainName $domainName;

    /**
     * @ORM\Column(type="text")
     */
    public string $destination;

    /**
     * @ORM\Column(type="boolean")
     */
    public bool $active = true;

    private function __construct(ForwardId $id, Space $space, string $address, DomainName $domainName)
    {
        $this->id = $id;
        $this->space = $space;

        $this->domainName = $domainName;
        $this->setAddress($address, $domainName);
    }

    public function setAddress(string $address, ?DomainName $domainName = null): void
    {
        $domainName ??= $this->domainName;
        $emailAddress = new EmailAddress($address . '@' . $domainName->namePair->toString());
        $emailAddress->validate();

        Assertion::same($emailAddress->label, '', 'Label is not allowed for a Forward address', 'address');
        Assertion::same($domainName->space, $this->space, 'DomainName must be part of the same Space', 'address');

        $this->address = $emailAddress->local;
        $this->domainName = $domainName;
    }

    public static function toAddress(ForwardId $id, Space $space, string $address, DomainName $domainName, EmailAddress $destination): self
    {
        $instance = new self($id, $space, $address, $domainName);
        $instance->setDestinationToAddress($destination);

        return $instance;
    }

    public static function toScript(ForwardId $id, Space $space, string $address, DomainName $domainName, string $destination): self
    {
        $instance = new self($id, $space, $address, $domainName);
        $instance->setDestinationToScript($destination);

        return $instance;
    }

    public function setDestinationToAddress(EmailAddress $destination): void
    {
        Assertion::false($destination->isPattern, 'Destination cannot be a pattern', 'destination');
        $destination->validate();

        $this->destination = 'address:' . $destination->toString();
    }

    public function setDestinationToScript(string $destination): void
    {
        $this->destination = 'script:' . $destination;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deActivate(): void
    {
        $this->active = false;
    }
}
