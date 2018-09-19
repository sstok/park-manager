<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\ServiceBusBundle\Guard\EventListener;

use ParkManager\Component\ServiceBus\MessageGuard\MessageAuthorizationFailed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class UnauthorizedExceptionListener implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event): void
    {
        /** @var MessageAuthorizationFailed $exception */
        $exception = $event->getException();

        if ($exception instanceof MessageAuthorizationFailed) {
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
