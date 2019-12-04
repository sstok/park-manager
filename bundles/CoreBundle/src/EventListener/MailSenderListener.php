<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

final class MailSenderListener implements EventSubscriberInterface
{
    public function onMessageSend(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (! $message instanceof Email) {
            return;
        }

        // always set the from address
        $message->from('noreply@example.com');
    }

    public static function getSubscribedEvents(): array
    {
        return [MessageEvent::class => 'onMessageSend'];
    }
}
