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

namespace ParkManager\Module\Webhosting\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bridge\Doctrine\EventSourcedEntityRepository;
use ParkManager\Module\Webhosting\Model\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Module\Webhosting\Model\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccount;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountRepository;
use Prooph\ServiceBus\EventBus;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class WebhostingAccountOrmRepository extends EventSourcedEntityRepository implements WebhostingAccountRepository
{
    public function __construct(EntityManagerInterface $entityManager, EventBus $eventBus, string $className = WebhostingAccount::class)
    {
        parent::__construct($entityManager, $eventBus, $className);
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
        $this->doTransactionalPersist($account);
        $this->doDispatchEvents($account);
    }

    public function remove(WebhostingAccount $account): void
    {
        if (!$account->isMarkedForRemoval()) {
            throw CannotRemoveActiveWebhostingAccount::withId($account->id());
        }

        $this->doTransactionalRemove($account);
        $this->doDispatchEvents($account);
    }
}
