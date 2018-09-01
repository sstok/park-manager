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

namespace ParkManager\Bridge\Twig\EventListener;

use ParkManager\Bridge\Twig\Response\TwigResponse;
use Psr\Container\ContainerInterface as Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The TwigResponseListener handles a TwigResponse.
 *
 * The Twig Engine is lazily loaded as not every request invokes
 * the Twig engine (webservice API for example).
 */
final class TwigResponseListener implements EventSubscriberInterface
{
    private $container;

    /**
     * @param Container $container Service container for loading *only* the Twig service (lazy)
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof TwigResponse || $response->isEmpty() || '' !== $response->getContent()) {
            return;
        }

        $newResponse = new Response(
            $this->container->get('twig')->render($response->getTemplate(), $response->getTemplateVariables()),
            $response->getStatusCode(),
            $response->headers->all()
        );

        if (null !== $charset = $response->getCharset()) {
            $newResponse->setCharset($charset);
        }

        $event->setResponse($newResponse);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -90], // Before ProfilerListener
        ];
    }
}
