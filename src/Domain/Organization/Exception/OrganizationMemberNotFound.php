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
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\UserId;

final class OrganizationMemberNotFound extends NotFoundException
{
    public static function with(OrganizationId $organization, UserId $user): self
    {
        return new self(
            sprintf(
                'User "%s" has no existing membership for Organization "%s".',
                $user->toString(),
                $organization->toString()
            ),
            [
                'user' => new EntityLink($user),
                'organization' => new EntityLink($organization),
            ]
        );
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('organization_member_not_found', $this->translationArgs, 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'User "{user}" has no existing membership for Organization "{organization}".';
    }
}
