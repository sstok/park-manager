<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\EventListener;

use ParkManager\UI\Web\Response\RouteRedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RouteRedirectResponseListener implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private Session $session;

    public function __construct(UrlGeneratorInterface $urlGenerator, Session $session)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (! $result instanceof RouteRedirectResponse) {
            return;
        }

        $flashes = $result->getFlashes();

        if (\count($flashes) > 0) {
            $flashBag = $this->session->getFlashBag();

            foreach ($flashes as $flash) {
                if ($flash[2] === null) {
                    $message = $flash[1];
                } else {
                    $message = ['message' => $flash[1], 'parameters' => $flash[2]];
                }

                $flashBag->add($flash[0], $message);
            }
        }

        $event->setResponse(
            new RedirectResponse(
                $this->urlGenerator->generate($result->getRoute(), $result->getParameters()),
                $result->getStatus()
            )
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => 'onKernelView'];
    }
}
