<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security\Guard;

use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\UserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * The BrowserKitAuthenticator is only to be used during BrowserKit tests.
 */
final class BrowserKitAuthenticator extends AbstractGuardAuthenticator
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function getCredentials(Request $request): array
    {
        return [
            'username' => $request->server->get('TEST_AUTH_USERNAME'),
            'password' => $request->server->get('TEST_AUTH_PASSWORD'),
            'password_new' => $request->server->get('TEST_AUTH_PASSWORD_NEW'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?SecurityUser
    {
        \assert(\is_array($credentials));
        \assert($userProvider instanceof UserProvider);

        $email = $credentials['username'];

        if ($email === null) {
            return null;
        }

        return $userProvider->loadUserByIdentifier($email);
    }

    /**
     * @param array        $credentials
     * @param SecurityUser $user
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        \assert(\is_array($credentials));
        \assert($user instanceof SecurityUser);

        if (! $user->isEnabled()) {
            throw new AuthenticationException();
        }

        if (! $this->userPasswordHasher->isPasswordValid($user, $credentials['password'])
            && ($credentials['password_new'] !== null
             && ! $this->userPasswordHasher->isPasswordValid($user, $credentials['password_new']))
        ) {
            throw new BadCredentialsException();
        }

        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response('Auth header required', 401);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $data = [
            'message' => \strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public function supports(Request $request): bool
    {
        return $request->server->has('TEST_AUTH_USERNAME');
    }
}
