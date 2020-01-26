<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\Webhosting\Plan\Exception\WebhostingPlanNotFound;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlan;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanId;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanRepository;

/**
 * @method WebhostingPlan|null find($id, $lockMode = null, $lockVersion = null)
 */
class WebhostingPlanOrmRepository extends EntityRepository implements WebhostingPlanRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = WebhostingPlan::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(WebhostingPlanId $id): WebhostingPlan
    {
        $plan = $this->find($id->toString());

        if ($plan === null) {
            throw WebhostingPlanNotFound::withId($id);
        }

        return $plan;
    }

    public function save(WebhostingPlan $plan): void
    {
        $this->_em->persist($plan);
    }

    public function remove(WebhostingPlan $plan): void
    {
        $this->_em->remove($plan);
    }
}
