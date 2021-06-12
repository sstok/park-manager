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
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotAssignDomainNameWithDifferentOwner extends DomainException implements TranslatableException
{
    private DomainNamePair $domainName;
    private ?SpaceId $current = null;
    private SpaceId $new;

    public static function toSpace(DomainNamePair $domainName, SpaceId $space): self
    {
        $instance = new self(
            sprintf(
                'Domain name "%s" does not have the same owner as Space "%s".',
                $domainName->toString(),
                $space->toString()
            )
        );

        $instance->domainName = $domainName;
        $instance->new = $space;

        return $instance;
    }

    public static function fromSpace(DomainNamePair $domainName, SpaceId $current, SpaceId $new): self
    {
        $instance = new self(
            sprintf(
                'Domain name "%s" of Space %s does not have the same owner as Space %s.',
                $domainName->toString(),
                $current->toString(),
                $new->toString()
            )
        );

        $instance->domainName = $domainName;
        $instance->current = $current;
        $instance->new = $new;

        return $instance;
    }

    public function getTranslatorId(): TranslatableMessage
    {
        if ($this->current) {
            return new TranslatableMessage(
                'domain_name.cannot_assign_domain_name_with_different_space_owner',
                $this->getTranslationArgs(),
                'validators'
            );
        }

        return new TranslatableMessage(
            'domain_name.cannot_assign_domain_name_with_different_owner',
            $this->getTranslationArgs(),
            'validators'
        );
    }

    public function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'current_space' => $this->current ? $this->current->toString() : '',
            'new_space' => $this->new->toString(),
        ];
    }
}
