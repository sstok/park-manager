<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

/**
 * @method Space|null find($id, $lockMode = null, $lockVersion = null)
 */
class WebhostingSpaceOrmRepository extends EntityRepository implements WebhostingSpaceRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = Space::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(SpaceId $id): Space
    {
        $space = $this->find($id->toString());

        if ($space === null) {
            throw WebhostingSpaceNotFound::withId($id);
        }

        return $space;
    }

    public function save(Space $space): void
    {
        $this->_em->persist($space);
    }

    public function remove(Space $space): void
    {
        if (! $space->isMarkedForRemoval()) {
            throw CannotRemoveActiveWebhostingSpace::withId($space->getId());
        }

        $this->_em->remove($space);
    }
}
