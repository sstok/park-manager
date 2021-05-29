<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use Doctrine\ORM\Mapping as ORM;

/**
 * A SystemRegistration keeps all information related to registration of a Webhosting Space or
 * or Virtualized Subdomain (subdomain with it's own server configuration instance).
 *
 * @ORM\Embeddable
 */
final class SystemRegistration
{
    /**
     * READ-ONLY. The system assigned user-id.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    public ?int $userId = null;

    /**
     * READ-ONLY. The user-groups that the system belongs to.
     *
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var array<int>
     */
    public ?array $userGroups = null;

    /**
     * READ-ONLY. The homedir that was assigned by the server.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $homeDir = null;

    /**
     * READ-ONLY. The system-cluster ID this entity is assigned to.
     *
     * Note. This is future planned for future and currently has no purpose.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $clusterId = null;

    /**
     * @param array<int, int> $userGroups
     */
    public function __construct(int $userId, array $userGroups, string $homeDir)
    {
        sort($userGroups, \SORT_REGULAR);

        $this->userId = $userId;
        $this->userGroups = $userGroups;
        $this->homeDir = $homeDir;
    }

    public function equals(self $other): bool
    {
        if ($this === $other) {
            return true;
        }

        if ($this->userId !== $other->userId) {
            return false;
        }

        if ($this->userGroups !== $other->userGroups) {
            return false;
        }

        return $this->homeDir === $other->homeDir;
    }
}
