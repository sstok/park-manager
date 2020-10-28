<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service\TLS;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectManagerDecorator;
use ParkManager\Tests\Mock\Domain\MockRepository;

/**
 * @internal
 */
final class TLSPersistenceRepositoryMock extends ObjectManagerDecorator
{
    use MockRepository;

    public function find($className, $id): ?object
    {
        try {
            return $this->mockDoGetByField('hash', $className . ':' . $id);
        } catch (EntityNotFoundException $e) {
            return null;
        }
    }

    public function persist($object): void
    {
        $this->mockDoSave($object);
    }

    protected function getFieldsIndexMapping(): array
    {
        return [
            'hash' => static fn (object $entity) => \get_class($entity) . ':' . $entity->getId(),
            'contents' => static fn (object $entity) => \get_class($entity) . ':' . $entity->getContents(),
        ];
    }

    protected function throwOnNotFound($key): void
    {
        [$class, $id] = \explode(':', $key, 2);

        throw EntityNotFoundException::fromClassNameAndIdentifier($class, ['id' => $id]);
    }
}
