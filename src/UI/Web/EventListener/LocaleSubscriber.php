<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\EventListener;

use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private array $acceptedLocales = [],
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        $securityUser = $event->getAuthenticationToken()->getUser();

        if (! $securityUser instanceof SecurityUser || $request->getSession()->get('_locale') !== null) {
            return;
        }

        $user = $this->userRepository->get(UserId::fromString($securityUser->getId()));
        $userLocale = $user->preferences->locale;

        if ($userLocale !== null) {
            $request->getSession()->set('_locale', $userLocale);
        }
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->attributes->has('_locale')) {
            return;
        }

        $locale = $this->trySingleRequestLocale($request) ??
                  $this->tryBySession($request) ??
                  $this->tryUserAgentPreferredLocale($request);

        $request->attributes->set('_locale', $locale);
        $request->setLocale($locale);
    }

    /**
     * With the locale query-parameter we only apply this
     * for the current request (not subsequent requests).
     */
    private function trySingleRequestLocale(Request $request): ?string
    {
        $locale = $request->query->get('locale');

        if (! \in_array($locale, $this->acceptedLocales, true)) {
            $locale = null;
        }

        return $locale;
    }

    private function tryBySession(Request $request): ?string
    {
        return $request->hasPreviousSession() ? $request->getSession()->get('_locale') : null;
    }

    private function tryUserAgentPreferredLocale(Request $request): ?string
    {
        $locale = $request->getPreferredLanguage($this->acceptedLocales);

        // Store the locale in the session to speed-up subsequent requests.
        if ($request->hasPreviousSession()) {
            $request->getSession()->set('_locale', $locale);
        }

        return $locale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                // must be registered after the Router to have access to the _locale,
                // but before LocaleListener and LocaleAwareListener
                ['onRequest', 20],
            ],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }
}
