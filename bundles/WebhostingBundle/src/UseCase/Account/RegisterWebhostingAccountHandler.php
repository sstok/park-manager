<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\UseCase\Account;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountRepository;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainName;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainNameRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanRepository;

final class RegisterWebhostingAccountHandler
{
    private $accountRepository;
    private $planRepository;
    private $domainNameRepository;

    public function __construct(WebhostingAccountRepository $accountRepository, WebhostingPlanRepository $planRepository, WebhostingDomainNameRepository $domainNameRepository)
    {
        $this->accountRepository    = $accountRepository;
        $this->planRepository       = $planRepository;
        $this->domainNameRepository = $domainNameRepository;
    }

    public function __invoke(RegisterWebhostingAccount $command): void
    {
        $domainName = $command->domainName();
        $planId     = $command->plan();

        $currentRegistration = $this->domainNameRepository->findByFullName($domainName);

        if ($currentRegistration !== null) {
            throw DomainNameAlreadyInUse::byAccountId($domainName, $currentRegistration->account()->id());
        }

        if ($planId !== null) {
            $account = WebhostingAccount::register(
                $command->id(),
                $command->owner(),
                $this->planRepository->get($planId)
            );
        } else {
            $account = WebhostingAccount::registerWithCustomCapabilities(
                $command->id(),
                $command->owner(),
                $command->customCapabilities()
            );
        }

        $primaryDomainName = WebhostingDomainName::registerPrimary($account, $domainName);

        $this->accountRepository->save($account);
        $this->domainNameRepository->save($primaryDomainName);
    }
}
