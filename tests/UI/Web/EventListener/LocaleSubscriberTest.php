<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\EventListener;

use ParkManager\Domain\User\User;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\UI\Web\EventListener\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * @internal
 */
final class LocaleSubscriberTest extends TestCase
{
    /** @test */
    public function interactive_login_ignore_unsupported_token_keeps_default(): void
    {
        $request = new Request();

        $subscriber = new LocaleSubscriber(new UserRepositoryMock());
        $subscriber->onInteractiveLogin(new InteractiveLoginEvent($request, new NullToken()));

        self::assertSame('en', $request->getLocale());
    }

    /** @test */
    public function interactive_login_gets_preference_with_empty_pref_keeps_default(): void
    {
        $request = $this->getRequestWithSession();

        $userRepositoryMock = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $token = $this->getToken($user);

        $subscriber = new LocaleSubscriber($userRepositoryMock);
        $subscriber->onInteractiveLogin(new InteractiveLoginEvent($request, $token));

        self::assertSame('en', $request->getLocale());
        self::assertFalse($request->getSession()->has('_locale'));
    }

    private function getRequestWithSession(bool $start = true): Request
    {
        $request = new Request();
        $request->setSession($session = new Session(new MockArraySessionStorage()));

        if ($start) {
            $session->start();
            $request->cookies->set($session->getName(), $session->getId());
        }

        return $request;
    }

    private function getToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(SecurityUser::fromEntity($user));

        return $token;
    }

    /** @test */
    public function interactive_login_gets_preference_with_locale_already_set_keeps_current(): void
    {
        $request = $this->getRequestWithSession();
        $request->getSession()->set('_locale', 'de');

        $userRepositoryMock = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $token = $this->getToken($user);

        $subscriber = new LocaleSubscriber($userRepositoryMock);
        $subscriber->onInteractiveLogin(new InteractiveLoginEvent($request, $token));

        self::assertSame('en', $request->getLocale());
        self::assertSame('de', $request->getSession()->get('_locale'));
    }

    /** @test */
    public function interactive_login_gets_preference_changes_current(): void
    {
        $request = $this->getRequestWithSession();

        $user = UserRepositoryMock::createUser();
        $user->preferences->setLocale('nl');

        $userRepositoryMock = new UserRepositoryMock([$user]);
        $token = $this->getToken($user);

        $subscriber = new LocaleSubscriber($userRepositoryMock);
        $subscriber->onInteractiveLogin(new InteractiveLoginEvent($request, $token));

        self::assertSame('en', $request->getLocale());
        self::assertSame('nl', $request->getSession()->get('_locale'));
    }

    /** @test */
    public function rejects_query_locale_when_already_set(): void
    {
        $request = new Request();
        $request->setLocale('uk');
        $request->attributes->set('_locale', 'uk');
        $request->query->set('locale', 'de');

        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['en', 'uk', 'de', 'nl']);
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('uk', $request->getLocale());
        self::assertSame('uk', $request->attributes->get('_locale'));
    }

    /** @test */
    public function rejects_query_locale_when_unsupported(): void
    {
        $request = new Request();
        $request->query->set('locale', 'de');

        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['en']);
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('en', $request->getLocale());
        self::assertSame('en', $request->attributes->get('_locale'));
    }

    /** @test */
    public function sets_locale_by_query_only_for_current_request(): void
    {
        $request = $this->getRequestWithSession();
        $request->query->set('locale', 'uk');

        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['en', 'uk', 'de', 'nl']);
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('uk', $request->getLocale());
        self::assertSame('uk', $request->attributes->get('_locale'));
        self::assertFalse($request->getSession()->has('_locale'));
    }

    /** @test */
    public function sets_locale_by_query_when_session_has_locale(): void
    {
        $request = $this->getRequestWithSession();
        $request->getSession()->set('_locale', 'de');
        $request->query->set('locale', 'uk');

        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['en', 'uk', 'de', 'nl']);
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('uk', $request->getLocale());
        self::assertSame('uk', $request->attributes->get('_locale'));
        self::assertSame('de', $request->getSession()->get('_locale'));
    }

    /** @test */
    public function it_ignores_locale_by_session_when_session_was_not_started(): void
    {
        $request = $this->getRequestWithSession(start: false);
        $request->getSession()->set('_locale', 'de');

        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['en', 'uk', 'de', 'nl']);
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('en', $request->getLocale());
        self::assertSame('en', $request->attributes->get('_locale'));
    }

    /** @test */
    public function sets_locale_by_session(): void
    {
        $request = $this->getRequestWithSession();
        $request->getSession()->set('_locale', 'de');

        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['en', 'uk', 'de', 'nl']);
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('de', $request->getLocale());
        self::assertSame('de', $request->attributes->get('_locale'));
        self::assertSame('de', $request->getSession()->get('_locale'));
    }

    /** @test */
    public function sets_locale_by_user_agent_preferred_language(): void
    {
        $subscriber = new LocaleSubscriber(new UserRepositoryMock(), ['nl', 'uk', 'de', 'nl']);

        $request = $this->getRequestWithSession();
        $request->headers->set('Accept-Language', 'de;q=0.7, *;q=0.5');
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('de', $request->getLocale());
        self::assertSame('de', $request->attributes->get('_locale'));
        self::assertSame('de', $request->getSession()->get('_locale'), 'Expected the resolved locale to be stored in the session.');

        $request = $this->getRequestWithSession();
        $request->headers->set('Accept-Language', '*;q=0.5, uk;q=0.7');
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('uk', $request->getLocale());
        self::assertSame('uk', $request->attributes->get('_locale'));
        self::assertSame('uk', $request->getSession()->get('_locale'), 'Expected the resolved locale to be stored in the session.');

        $request = $this->getRequestWithSession();
        $request->headers->set('Accept-Language', 'ru');
        $subscriber->onRequest($this->createRequestEvent($request));

        self::assertSame('nl', $request->getLocale());
        self::assertSame('nl', $request->attributes->get('_locale'));
        self::assertSame('nl', $request->getSession()->get('_locale'), 'Expected the resolved locale to be stored in the session.');
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
