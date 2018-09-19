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

namespace ParkManager\Bridge\Twig\Tests\EventListener;

use ParkManager\Bridge\Twig\EventListener\TwigResponseListener;
use ParkManager\Bridge\Twig\Response\TwigResponse;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

/**
 * @internal
 */
final class TwigResponseListenerTest extends TestCase
{
    /** @test */
    public function it_ignores_other_responses()
    {
        $container = $this->createUnusedContainer();
        $listener  = new TwigResponseListener($container);

        $event = $this->createEvent($response = new Response());
        $listener->onKernelResponse($event);

        self::assertSame($response, $event->getResponse());
    }

    /** @test */
    public function it_ignores_empty_response()
    {
        $container = $this->createUnusedContainer();
        $listener  = new TwigResponseListener($container);

        $event = $this->createEvent(new TwigResponse('Nope', [], 204));
        $listener->onKernelResponse($event);

        self::assertSame('', $event->getResponse()->getContent());
    }

    /** @test */
    public function it_ignores_when_content_is_already_set()
    {
        $container = $this->createUnusedContainer();
        $listener  = new TwigResponseListener($container);

        $event = $this->createEvent((new TwigResponse('Nope'))->setContent('Something'));
        $listener->onKernelResponse($event);

        self::assertSame('Something', $event->getResponse()->getContent());
    }

    /** @test */
    public function it_renders_twig_template()
    {
        $container = $this->createUsedContainer('@CoreModule/client/show_user.html.twig', ['He' => 'you']);
        $listener  = new TwigResponseListener($container);

        $event = $this->createEvent(new TwigResponse('@CoreModule/client/show_user.html.twig', ['He' => 'you']));
        $listener->onKernelResponse($event);

        self::assertNotInstanceOf(TwigResponse::class, $event->getResponse(), 'TwigResponse should be replaced with a Response');
        self::assertSame('It was like this when I got here.', $event->getResponse()->getContent());
    }

    private function createUnusedContainer(): ContainerInterface
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('twig')->shouldNotBeCalled();

        return $containerProphecy->reveal();
    }

    private function createUsedContainer(string $template, array $variables): ContainerInterface
    {
        $twig = $this->prophesize(Environment::class);
        $twig->render($template, $variables)->willReturn('It was like this when I got here.');

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('twig')->willReturn($twig->reveal());

        return $containerProphecy->reveal();
    }

    private function createEvent(Response $response): FilterResponseEvent
    {
        return new FilterResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );
    }
}
