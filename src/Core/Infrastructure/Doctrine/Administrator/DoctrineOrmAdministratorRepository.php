<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Core\Infrastructure\Doctrine\Administrator;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bundle\UserBundle\Model\DoctrineOrmUserCollection;
use ParkManager\Component\Model\Event\EventEmitter;
use ParkManager\Core\Domain\Administrator\Administrator;
use ParkManager\Core\Domain\Administrator\AdministratorRepository;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DoctrineOrmAdministratorRepository extends DoctrineOrmUserCollection implements AdministratorRepository
{
    public function __construct(EntityManagerInterface $entityManager, EventEmitter $eventEmitter)
    {
        parent::__construct($entityManager, $eventEmitter, Administrator::class);
    }
}
