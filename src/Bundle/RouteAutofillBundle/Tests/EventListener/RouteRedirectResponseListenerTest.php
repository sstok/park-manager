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

namespace ParkManager\Bundle\RouteAutofillBundle\Tests\EventListener;

use ParkManager\Bundle\RouteAutofillBundle\MappingFileLoader;
use ParkManager\Bundle\RouteAutofillBundle\EventListener\RouteRedirectResponseListener;
use ParkManager\Bundle\RouteAutofillBundle\Response\RouteRedirectResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
final class RouteRedirectResponseListenerTest extends TestCase
{
    /** @test */
    public function it_ignores_other_responses()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $listener = new RouteRedirectResponseListener($urlGenerator);
        $event = $this->createEvent(false);

        $listener->onKernelView($event);

        self::assertFalse($event->hasResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    /** @test */
    public function it_sets_a_redirect_response()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy
            ->generate('foobar', ['he' => 'bar'])
            ->willReturn('https://park-manager.com/webhosting');
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $listener = new RouteRedirectResponseListener($urlGenerator);
        $event = $this->createEvent(new RouteRedirectResponse('foobar', ['he' => 'bar']));

        $listener->onKernelView($event);

        $this->assertResponseIsRedirect($event->getResponse(), 'https://park-manager.com/webhosting');
    }

    /** @test */
    public function it_sets_a_redirect_response_with_custom_status()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy
            ->generate('foobar', ['he' => 'bar'])
            ->willReturn('https://park-manager.com/webhosting');
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $listener = new RouteRedirectResponseListener($urlGenerator);
        $event = $this->createEvent(RouteRedirectResponse::permanent('foobar', ['he' => 'bar']));

        $listener->onKernelView($event);

        $this->assertResponseIsRedirect(
            $event->getResponse(),
            'https://park-manager.com/webhosting',
            RedirectResponse::HTTP_MOVED_PERMANENTLY
        );
    }

    private function createEvent($result): GetResponseForControllerResultEvent
    {
        return new GetResponseForControllerResultEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $result
        );
    }

    private function assertResponseIsRedirect($response, string $expectedTargetUrl, int $expectedStatus = 302): void
    {
        /**
         * @var RedirectResponse $response
         */
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals($expectedTargetUrl, $response->getTargetUrl());
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }
}
