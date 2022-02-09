<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use Psr\Container\ContainerInterface;

final class RepositoryLocator
{
    public function __construct(
        private ContainerInterface $repositoryServices,
        private array $entityShortAliases = []
    ) {
    }

    /**
     * @param class-string|string $entityName
     */
    public function get(string $entityName): object
    {
        if ($this->repositoryServices->has($entityName)) {
            return $this->repositoryServices->get($entityName);
        }

        if (! isset($this->entityShortAliases[$entityName])) {
            throw new \InvalidArgumentException(sprintf('No Repository registered for entity-alias "%s".', $entityName));
        }

        return $this->repositoryServices->get($this->entityShortAliases[$entityName]);
    }

    /**
     * @param class-string|object $id the EntityId class-name or an EntityId object
     */
    public function getById(string | object $id): object
    {
        if (\is_object($id)) {
            $id = $id::class;
        }

        // Remove 'Id' from the entity.
        $id = mb_substr($id, 0, -2);

        return $this->get($id);
    }
}
