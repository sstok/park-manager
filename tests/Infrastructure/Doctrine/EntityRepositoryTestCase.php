<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\ResultSet;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group functional
 *
 * @property object $repository
 */
abstract class EntityRepositoryTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    protected function getEntityManager(?string $manager = 'doctrine.orm.default_entity_manager'): EntityManagerInterface
    {
        return self::getContainer()->get($manager ?? $this->getDefaultManagerName());
    }

    protected function getDefaultManagerName(): string
    {
        return 'doctrine.orm.default_entity_manager';
    }

    /**
     * @param array<array-key, string> $expected  IDs provided as string-array
     * @param ResultSet<mixed>         $resultSet
     */
    protected function assertIdsEquals(array $expected, ResultSet $resultSet): void
    {
        $resultIds = [];

        foreach ($resultSet as $entity) {
            $resultIds[$entity->id->toString()] = $entity;
        }

        static::assertSame($expected, array_keys($resultIds));
    }

    /**
     * @param array<int, object>          $expectedIds
     * @param iterable<array-key, object> $result
     */
    protected function assertEntitiesEquals(array $expectedIds, iterable $result): void
    {
        $found = [];
        $expected = [];

        foreach ($result as $entity) {
            $found[$entity->id->toString()] = $entity;
        }

        foreach ($expectedIds as $id) {
            $expected[$id->toString()] = $this->repository->get($id);
        }

        ksort($expected, \SORT_STRING);
        ksort($found, \SORT_STRING);

        static::assertSame($expected, $found);
    }
}
