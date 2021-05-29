<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization\Exception;

use ParkManager\Domain\Exception\InvalidArgument;
use ParkManager\Domain\Organization\OrganizationId;

final class CannotRemoveInternalOrganization extends InvalidArgument
{
    private OrganizationId $id;

    private function __construct(string $message, OrganizationId $id)
    {
        parent::__construct($message, 404);

        $this->id = $id;
    }

    public static function withId(OrganizationId $id): self
    {
        return new self(
            sprintf('Organization with id "%s" is marked as internal and cannot be removed.', $id->toString()),
            $id,
        );
    }

    public function getTranslatorId(): string
    {
        return 'cannot_remove_internal_organization';
    }

    public function getTranslationArgs(): array
    {
        return ['organization' => $this->id->toString()];
    }
}
