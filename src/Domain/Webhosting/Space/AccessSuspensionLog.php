<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'space_suspensions_log')]
class AccessSuspensionLog
{
    /**
     * Pseudo ID column for Doctrine.
     */
    #[Id]
    #[Column(name: 'log_id')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    public function __construct(
        #[ManyToOne(targetEntity: Space::class, inversedBy: 'suspensions')]
        #[JoinColumn(name: 'space_id', onDelete: 'CASCADE')]
        public Space $space,

        #[Column(name: 'log_suspension_level', nullable: true, enumType: SuspensionLevel::class)]
        public SuspensionLevel | null $level,

        #[Column(name: 'log_timestamp', type: 'datetime_immutable')]
        public DateTimeImmutable $timestamp
    ) {
    }
}
