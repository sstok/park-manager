<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

use Doctrine\Common\Collections\Criteria;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

final class FtpUserUsageValidator implements DomainNameSpaceUsageValidator
{
    public function __construct(private FtpUserRepository $ftpUserRepository)
    {
    }

    public function __invoke(DomainName $domainName, Space $space): array
    {
        return [
            FtpUser::class => $this->ftpUserRepository->all($space->id)
                ->filter(Criteria::expr()->eq('domainName', $domainName))
                ->setLimit(20),
        ];
    }
}
