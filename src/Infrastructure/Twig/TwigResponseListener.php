<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use ParkManager\UI\Web\Response\TwigResponse;
use Psr\Container\ContainerInterface as Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * The TwigResponseListener handles a TwigResponse.
 *
 * The Twig Engine is lazily loaded as not every request invokes
 * the Twig engine (webservice API for example).
 */
final class TwigResponseListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private Container $container;

    /**
     * @param Container $container Service container for loading *only* the Twig service (lazy)
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (! $response instanceof TwigResponse || $response->isEmpty() || $response->getContent() !== '') {
            return;
        }

        // Note: This cannot be done different. Using a sendContent approach breaks the Profiler toolbar
        // as the content is set to late.

        $response->setContent(
            $this->container->get('twig')->render($response->getTemplate(), $response->getTemplateVariables())
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -90], // Before ProfilerListener
        ];
    }

    public static function getSubscribedServices(): array
    {
        return [
            'twig' => Environment::class,
        ];
    }
}
