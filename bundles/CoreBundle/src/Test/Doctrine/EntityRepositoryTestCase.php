<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Test\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EntityRepositoryTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    protected function getEntityManager(?string $manager = 'doctrine.orm.default_entity_manager'): EntityManagerInterface
    {
        /** @var EntityManagerInterface $manager */
        return self::$container->get($manager ?? $this->getDefaultManagerName());
    }

    protected function getDefaultManagerName(): string
    {
        return 'doctrine.orm.default_entity_manager';
    }
}
