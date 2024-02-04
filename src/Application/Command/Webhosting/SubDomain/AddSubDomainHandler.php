<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\SubDomain;

use ParkManager\Application\Service\TLS\CertificateFactory;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceIsSuspended;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;

final class AddSubDomainHandler
{
    public function __construct(
        private DomainNameRepository $domainNameRepository,
        private SubDomainRepository $subDomainRepository,
        private CertificateFactory $certificateFactory
    ) {}

    public function __invoke(AddSubDomain $command): void
    {
        $domainName = $this->domainNameRepository->get($command->domainNameId);
        $subDomain = new SubDomain($command->id, $domainName, $command->name, $command->homeDir, $command->config);
        $space = $subDomain->space;

        if (SuspensionLevel::equalsToAny($space->accessSuspended, SuspensionLevel::ACCESS_RESTRICTED, SuspensionLevel::LOCKED)) {
            throw new WebhostingSpaceIsSuspended($space->id, $space->accessSuspended);
        }

        if ($command->certificate !== null) {
            $certificate = $this->certificateFactory->createCertificate($command->certificate, $command->privateKey, $command->caList);
            $subDomain->assignTlsConfiguration($certificate);
        }

        $this->subDomainRepository->save($subDomain);
    }
}
