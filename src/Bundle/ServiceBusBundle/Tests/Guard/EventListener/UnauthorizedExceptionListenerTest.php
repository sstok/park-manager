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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\Guard\EventListener;

use ParkManager\Bundle\ServiceBusBundle\Guard\EventListener\UnauthorizedExceptionListener;
use ParkManager\Component\ServiceBus\MessageGuard\MessageAuthorizationFailed;
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
        $listener      = new UnauthorizedExceptionListener();
        $event         = new GetResponseForExceptionEvent(
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
        $listener      = new UnauthorizedExceptionListener();
        $event         = new GetResponseForExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception = MessageAuthorizationFailed::forMessage($message = new \stdClass())
        );

        $listener->onException($event);

        $expectedException = new AccessDeniedException($exception->getMessage(), $exception);
        $expectedException->setSubject($message);

        self::assertEquals($expectedException, $event->getException());
    }
}
