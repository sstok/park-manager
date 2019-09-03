<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Doctrine\Account;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bundle\CoreBundle\Doctrine\EventSourcedEntityRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountRepository;
use Symfony\Component\Messenger\MessageBusInterface as MessageBus;

/**
 * @method WebhostingAccount|null find($id, $lockMode = null, $lockVersion = null)
 */
class WebhostingAccountOrmRepository extends EventSourcedEntityRepository implements WebhostingAccountRepository
{
    public function __construct(EntityManagerInterface $entityManager, MessageBus $eventBus, string $className = WebhostingAccount::class)
    {
        parent::__construct($entityManager, $eventBus, $className);
    }

    public function get(WebhostingAccountId $id): WebhostingAccount
    {
        $account = $this->find($id->toString());

        if ($account === null) {
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
        if (! $account->isMarkedForRemoval()) {
            throw CannotRemoveActiveWebhostingAccount::withId($account->getId());
        }

        $this->_em->remove($account);
        $this->doDispatchEvents($account);
    }
}
