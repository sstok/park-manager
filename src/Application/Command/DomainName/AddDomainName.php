<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\OwnerId;
use ParkManager\Infrastructure\Validator\Constraints\RegistrableDomainName;

final class AddDomainName
{
    public function __construct(
        public DomainNameId $id,
        public OwnerId $owner,
        #[RegistrableDomainName]
        public DomainNamePair $name
    ) {
    }

    public static function with(string $id, string $ownerId, string $name, string $tld): self
    {
        return new self(DomainNameId::fromString($id), OwnerId::fromString($ownerId), new DomainNamePair($name, $tld));
    }
}
