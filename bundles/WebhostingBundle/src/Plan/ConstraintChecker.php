<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Plan;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountRepository;
use Psr\Container\ContainerInterface;

final class ConstraintChecker
{
    /** @var ContainerInterface */
    private $constraintValidators;

    /** @var WebhostingAccountRepository */
    private $accountRepository;

    public function __construct(ContainerInterface $constraintValidators, WebhostingAccountRepository $accountRepository)
    {
        $this->constraintValidators = $constraintValidators;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @throws ConstraintExceeded
     */
    public function validate(WebhostingAccountId $accountId, string $constraintName, array $context = []): void
    {
        $constraints = $this->accountRepository->get($accountId)->getPlanConstraints();

        if (! $constraints->has($constraintName)) {
            return;
        }

        $validator = $this->constraintValidators->get($constraintName);
        \assert($validator instanceof ConstraintValidator);
        $validator->validate($accountId, $constraints->get($constraintName), $context);
    }
}
