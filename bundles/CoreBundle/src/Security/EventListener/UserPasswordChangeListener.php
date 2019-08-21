<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Security\EventListener;

use ParkManager\Bundle\CoreBundle\Domain\Administrator\Event\AdministratorPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Domain\Client\Event\ClientPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Event\UserPasswordWasChanged;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as EventDispatcher;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface as MessageSubscriber;

final class UserPasswordChangeListener implements MessageSubscriber
{
    /** @var EventDispatcher */
    private $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ClientPasswordWasChanged|AdministratorPasswordWasChanged $message
     */
    public function __invoke(object $message): void
    {
        $this->eventDispatcher->dispatch(
            new UserPasswordWasChanged(
                $message->getId()->toString(),
                $message->getPassword()
            )
        );
    }

    public static function getHandledMessages(): iterable
    {
        yield ClientPasswordWasChanged::class;
        yield AdministratorPasswordWasChanged::class;
    }
}
