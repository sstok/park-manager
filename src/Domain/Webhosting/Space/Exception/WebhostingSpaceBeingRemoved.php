<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space\Exception;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Exception\TranslatableException;

final class WebhostingSpaceBeingRemoved extends \RuntimeException implements TranslatableException
{
    public DomainNamePair $name;

    public function __construct(DomainNamePair $name)
    {
        parent::__construct(
            sprintf(
                'Webhosting space %s is currently being removed (or is marked for removal) and cannot be updated or changed.',
                $name->toString()
            )
        );

        $this->name = $name;
    }

    public function getTranslatorId(): string
    {
        return 'webhosting_space.is_being_removed';
    }

    public function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->name->toString(),
        ];
    }
}
