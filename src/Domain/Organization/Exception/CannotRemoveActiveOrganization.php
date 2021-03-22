<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization\Exception;

use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\ResultSet;

final class CannotRemoveActiveOrganization extends \DomainException
{
    public OrganizationId $id;

    /**
     * The entities organization per ["EntityName" => {ResultSet<EntityName>}].
     *
     * @var array<class-string, ResultSet>
     */
    public array $activeEntities;

    public function __construct(OrganizationId $id, array $activeEntities)
    {
        parent::__construct('Organization with id "%s" is still assigned as Owner to 1 or more entities. Change their Owner assignment first.');

        $this->id = $id;
        $this->activeEntities = $activeEntities;
    }
}
