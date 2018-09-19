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

namespace ParkManager\Bundle\RouteAutofillBundle\Tests\EventListener;

use ParkManager\Bundle\RouteAutofillBundle\EventListener\RouteRedirectResponseListener;
use ParkManager\Bundle\RouteAutofillBundle\Response\RouteRedirectResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $event    = $this->createEvent(false);

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
        $event    = $this->createEvent(new RouteRedirectResponse('foobar', ['he' => 'bar']));

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
        $event    = $this->createEvent(RouteRedirectResponse::permanent('foobar', ['he' => 'bar']));

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
