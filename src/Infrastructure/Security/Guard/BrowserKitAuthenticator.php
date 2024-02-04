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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * The BrowserKitAuthenticator is only to be used during BrowserKit tests.
 */
final class BrowserKitAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private UserProvider $userProvider
    ) {}

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new Response('Auth header required', 401);
    }

    public function supports(Request $request): ?bool
    {
        return $request->server->has('TEST_AUTH_USERNAME');
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = [
            'username' => $username = $request->server->get('TEST_AUTH_USERNAME'),
            'password' => $request->server->get('TEST_AUTH_PASSWORD'),
            'password_new' => $request->server->get('TEST_AUTH_PASSWORD_NEW'),
        ];

        return new Passport(
            new UserBadge($username, [$this->userProvider, 'loadUserByIdentifier']),
            new CustomCredentials([$this, 'checkCredentials'], $credentials)
        );
    }

    public function checkCredentials(array $credentials, SecurityUser $user): bool
    {
        if ($this->userPasswordHasher->isPasswordValid($user, $credentials['password'])) {
            return true;
        }

        return $credentials['password_new'] !== null
               && $this->userPasswordHasher->isPasswordValid($user, $credentials['password_new']);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }
}
