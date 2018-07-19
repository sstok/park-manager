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

namespace ParkManager\Bundle\ServiceBusBundle\Guard\EventListener;

use ParkManager\Component\ServiceBus\MessageGuard\UnauthorizedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class UnauthorizedExceptionListener implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event): void
    {
        /** @var $exception UnauthorizedException */
        if (($exception = $event->getException()) instanceof UnauthorizedException) {
            $newException = new AccessDeniedException($exception->getMessage(), $exception);
            $newException->setSubject($exception->getMessageName());
            $event->setException($newException);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]]; // Before the Firewall ExceptionListener
    }
}
