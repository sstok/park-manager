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

namespace ParkManager\Bridge\ServiceBus\Tests\Guard\EventListener;

use ParkManager\Bridge\ServiceBus\Guard\EventListener\UnauthorizedExceptionListener;
use ParkManager\Component\ServiceBus\MessageGuard\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
final class UnauthorizedExceptionListenerTest extends TestCase
{
    /** @test */
    public function it_ignore_other_exceptions()
    {
        $listener = new UnauthorizedExceptionListener();
        $event = new GetResponseForExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception = new \RuntimeException('Oh boy, that escalated quickly.')
        );

        $listener->onException($event);

        self::assertSame($exception, $event->getException());
    }

    /** @test */
    public function it_changes_UnauthorizedException_to_AccessDeniedException()
    {
        $listener = new UnauthorizedExceptionListener();
        $event = new GetResponseForExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception = UnauthorizedException::forMessage($message = new \stdClass())
        );

        $listener->onException($event);

        $expectedException = new AccessDeniedException($exception->getMessage(), $exception);
        $expectedException->setSubject($message);

        self::assertEquals($expectedException, $event->getException());
    }
}
