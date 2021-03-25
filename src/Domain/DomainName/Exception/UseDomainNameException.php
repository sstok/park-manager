<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use DomainException;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Space\SpaceId;

abstract class UseDomainNameException extends DomainException
{
    public DomainNamePair $domainName;
    public SpaceId $current;
    /** @var array<class-string, array<int, object>> [EntityName => [entities]] */
    public array $entities;

    /**
     * @param array<class-string, array<int, object>> [EntityName => [entities]]
     */
    public function __construct(DomainNamePair $domainName, SpaceId $current, array $entities)
    {
        $message = $this->getInitMessage($domainName, $current);

        foreach ($entities as $className => $entitiesList) {
            $message .= "{$className}: \n";

            foreach ($entitiesList as $entity) {
                $message .= "- {$entity->id->toString()}\n";
            }

            $message .= "\n";
        }

        parent::__construct($message);

        $this->domainName = $domainName;
        $this->current = $current;
        $this->entities = $entities;
    }

    abstract protected function getInitMessage(DomainNamePair $domainName, SpaceId $current): string;
}
