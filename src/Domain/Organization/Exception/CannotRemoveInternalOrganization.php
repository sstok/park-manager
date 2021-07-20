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
use ParkManager\Domain\Translation\TranslatableMessage;

final class CannotRemoveInternalOrganization extends InvalidArgument
{
    private function __construct(string $message, private OrganizationId $id)
    {
        parent::__construct($message, 404);
    }

    public static function withId(OrganizationId $id): self
    {
        return new self(
            sprintf('Organization with id "%s" is marked as internal and cannot be removed.', $id->toString()),
            $id,
        );
    }

    public function getTranslatorId(): TranslatableMessage
    {
        return new TranslatableMessage('cannot_remove_internal_organization', ['organization' => $this->id], 'validators');
    }
}
