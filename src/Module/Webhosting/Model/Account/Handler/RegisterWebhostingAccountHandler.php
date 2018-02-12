<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting\Model\Account\Handler;

use ParkManager\Module\Webhosting\Model\Account\Command\RegisterWebhostingAccount;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccount;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountRepository;
use ParkManager\Module\Webhosting\Model\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Module\Webhosting\Model\DomainName\WebhostingDomainName;
use ParkManager\Module\Webhosting\Model\DomainName\WebhostingDomainNameRepository;
use ParkManager\Module\Webhosting\Model\Package\WebhostingPackageRepository;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterWebhostingAccountHandler
{
    private $accountRepository;
    private $packageRepository;
    private $domainNameRepository;

    public function __construct(
        WebhostingAccountRepository $accountRepository,
        WebhostingPackageRepository $packageRepository,
        WebhostingDomainNameRepository $domainNameRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->packageRepository = $packageRepository;
        $this->domainNameRepository = $domainNameRepository;
    }

    public function __invoke(RegisterWebhostingAccount $command): void
    {
        /** @var WebhostingAccount|string $className */
        $className = $this->accountRepository->getModelClass();
        $domainName = $command->domainName();

        if (null !== $currentRegistration = $this->domainNameRepository->getByFullName($domainName)) {
            throw DomainNameAlreadyInUse::byAccountId($domainName, $currentRegistration->account()->id());
        }

        if (null !== $packageId = $command->package()) {
            /** @var WebhostingAccount $account */
            $account = $className::register(
                $command->id(),
                $command->owner(),
                $this->packageRepository->get($packageId)
            );
        } else {
            /** @var WebhostingAccount $account */
            $account = $className::registerWithCustomCapabilities(
                $command->id(),
                $command->owner(),
                $command->customCapabilities()
            );
        }

        /** @var WebhostingDomainName|string $primaryDomainNameClass */
        $primaryDomainNameClass = $this->domainNameRepository->getModelClass();
        $primaryDomainName = $primaryDomainNameClass::registerPrimary($account, $domainName);

        $this->accountRepository->save($account);
        $this->domainNameRepository->save($primaryDomainName);
    }
}
