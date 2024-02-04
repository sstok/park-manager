<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotTransferPrimaryDomainName extends \DomainException implements DomainError
{
    public function __construct(public DomainNamePair $domainName, public SpaceId $current, public ?SpaceId $new)
    {
        parent::__construct(
            sprintf(
                'Domain name "%s" of space %s is marked as primary and cannot be transferred to space %s.',
                $domainName->toString(),
                $current->toString(),
                $new ? $new->toString() : '[owner]'
            )
        );
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage(
            'domain_name.cannot_transfer_space_primary_domain_name',
            [
                'domain_name' => $this->domainName->name,
                'domain_tld' => $this->domainName->tld,
                'current_space' => new EntityLink($this->current),
                'new_space' => new EntityLink($this->new),
            ],
            'validators'
        );
    }

    public function getPublicMessage(): string
    {
        return 'DomainName "{domainName}" of space "{current}" is marked as primary and cannot be transferred to space "{new}".';
    }
}
