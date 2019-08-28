<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Doctrine\Package;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bundle\CoreBundle\Doctrine\EventSourcedEntityRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Package\Exception\WebhostingPackageNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\Package\WebhostingPackage;
use ParkManager\Bundle\WebhostingBundle\Model\Package\WebhostingPackageId;
use ParkManager\Bundle\WebhostingBundle\Model\Package\WebhostingPackageRepository;
use Symfony\Component\Messenger\MessageBusInterface as MessageBus;

/**
 * @method WebhostingPackage|null find($id, $lockMode = null, $lockVersion = null)
 */
final class WebhostingPackageOrmRepository extends EventSourcedEntityRepository implements WebhostingPackageRepository
{
    public function __construct(EntityManagerInterface $entityManager, MessageBus $eventBus, string $className = WebhostingPackage::class)
    {
        parent::__construct($entityManager, $eventBus, $className);
    }

    public function get(WebhostingPackageId $id): WebhostingPackage
    {
        $package = $this->find($id->toString());

        if ($package === null) {
            throw WebhostingPackageNotFound::withId($id);
        }

        return $package;
    }

    public function save(WebhostingPackage $package): void
    {
        $this->_em->persist($package);
        $this->doDispatchEvents($package);
    }

    public function remove(WebhostingPackage $package): void
    {
        $this->_em->remove($package);
        $this->doDispatchEvents($package);
    }
}
