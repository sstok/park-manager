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

namespace ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Account;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Component\DomainEvent\EventEmitter;
use ParkManager\Module\CoreModule\Infrastructure\Doctrine\EventSourcedEntityRepository;
use ParkManager\Module\WebhostingModule\Domain\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountRepository;

final class WebhostingAccountOrmRepository extends EventSourcedEntityRepository implements WebhostingAccountRepository
{
    public function __construct(EntityManagerInterface $entityManager, EventEmitter $eventEmitter, string $className = WebhostingAccount::class)
    {
        parent::__construct($entityManager, $eventEmitter, $className);
    }

    public function get(WebhostingAccountId $id): WebhostingAccount
    {
        /** @var WebhostingAccount|null $account */
        $account = $this->find($id->toString());

        if (null === $account) {
            throw WebhostingAccountNotFound::withId($id);
        }

        return $account;
    }

    public function save(WebhostingAccount $account): void
    {
        $this->_em->persist($account);
        $this->doDispatchEvents($account);
    }

    public function remove(WebhostingAccount $account): void
    {
        if (!$account->isMarkedForRemoval()) {
            throw CannotRemoveActiveWebhostingAccount::withId($account->id());
        }

        $this->_em->remove($account);
        $this->doDispatchEvents($account);
    }
}
