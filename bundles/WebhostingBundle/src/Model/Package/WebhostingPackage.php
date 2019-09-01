<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Package;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Bundle\CoreBundle\Model\DomainEventsCollectionTrait;
use ParkManager\Bundle\CoreBundle\Model\RecordsDomainEvents;
use ParkManager\Bundle\WebhostingBundle\Model\Package\Event\WebhostingPackageCapabilitiesWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Package\Event\WebhostingPackageWasCreated;

/**
 * @ORM\Entity()
 * @ORM\Table(name="package", schema="webhosting")
 */
class WebhostingPackage implements RecordsDomainEvents
{
    use DomainEventsCollectionTrait;

    /**
     * @ORM\Id()
     * @ORM\Column(type="park_manager_webhosting_package_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var WebhostingPackageId
     */
    protected $id;

    /**
     * @ORM\Column(name="capabilities", type="webhosting_capabilities", nullable=true)
     *
     * @var Capabilities
     */
    protected $capabilities;

    /**
     * @ORM\Column(name="metadata", type="json")
     *
     * @var array
     */
    private $metadata = [];

    protected function __construct(WebhostingPackageId $id, Capabilities $capabilities)
    {
        $this->id           = $id;
        $this->capabilities = $capabilities;
    }

    /**
     * @return static
     */
    public static function create(WebhostingPackageId $id, Capabilities $capabilities)
    {
        $instance = new static($id, $capabilities);
        $instance->recordThat(new WebhostingPackageWasCreated($id, $capabilities));

        return $instance;
    }

    public function id(): WebhostingPackageId
    {
        return $this->id;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }

    public function changeCapabilities(Capabilities $capabilities): void
    {
        if ($capabilities->equals($this->capabilities)) {
            return;
        }

        $this->capabilities = $capabilities;
        $this->recordThat(new WebhostingPackageCapabilitiesWasChanged($this->id, $capabilities));
    }

    /**
     * Set some (scalar) metadata information for the webhosting package.
     *
     * This information should only contain informational values
     * (eg. the label, description, etc).
     *
     * Not something that be used as a Domain policy. either,
     * don't use this for pricing or storing user-type limitations.
     *
     * Changing the metadata doesn't dispatch a Domain event.
     */
    public function withMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }
}
