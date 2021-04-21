<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="space_suspensions_log")
 */
class AccessSuspensionLog
{
    /**
     * Pseudo ID column for Doctrine.
     *
     * @ORM\Id
     * @ORM\Column(name="log_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Space::class, inversedBy="suspensions")
     * @ORM\JoinColumn(name="space_id", onDelete="CASCADE")
     */
    public Space $space;

    /**
     * @ORM\Column(name="log_suspension_level", type="park_manager_webhosting_suspension_level", nullable=true)
     */
    public ?SuspensionLevel $level;

    /**
     * @ORM\Column(name="log_timestamp", type="datetime_immutable")
     */
    public DateTimeImmutable $timestamp;

    public function __construct(Space $space, ?SuspensionLevel $level, DateTimeImmutable $timestamp)
    {
        $this->space = $space;
        $this->level = $level;
        $this->timestamp = $timestamp;
    }
}
