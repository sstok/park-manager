<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization\Exception;

use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\Organization\OrganizationId;

final class OrganizationNotFound extends NotFoundException
{
    public static function withId(OrganizationId $id): self
    {
        return new self(
            sprintf('Organization with id "%s" does not exist.', $id->toString()),
            [
                'organization' => $id,
            ]
        );
    }

    public function getTranslatorId(): string
    {
        return 'organization_not_found';
    }
}
