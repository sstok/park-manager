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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface as UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface as CsrfTokenManager;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class FormAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    /** @var CsrfTokenManager */
    private $csrfTokenManager;

    /** @var UserPasswordEncoder */
    private $passwordEncoder;

    /** @var UrlGenerator */
    private $urlGenerator;

    public function __construct(CsrfTokenManager $csrfTokenManager, UserPasswordEncoder $passwordEncoder, UrlGenerator $urlGenerator)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->urlGenerator = $urlGenerator;
    }

    public function getCredentials(Request $request): array
    {
        $csrfToken = $request->request->get('_csrf_token');

        if ($this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken)) === false) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        $email = $request->request->get('_email');

        if ($request->hasSession()) {
            $request->getSession()->set(Security::LAST_USERNAME, $email);
        }

        return [
            'email' => $email,
            'password' => $request->request->get('_password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
    }

    /**
     * @param array        $credentials
     * @param UserProvider $userProvider
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?SecurityUser
    {
        $email = $credentials['email'];

        if ($email === null || ! \is_string($email)) {
            return null;
        }

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $credentials['csrf_token']))) {
            throw new InvalidCsrfTokenException();
        }

        return $userProvider->loadUserByUsername($email);
    }

    /**
     * @param array $credentials
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        if (! $this->passwordEncoder->isPasswordValid($user, $credentials['password'])) {
            throw new BadCredentialsException();
        }

        if (! $user->isEnabled()) {
            throw new DisabledException();
        }

        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        $targetPath = null;

        if ($request->hasSession()) {
            $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        }

        if (! $targetPath) {
            $targetPath = $this->getDefaultSuccess($request);
        }

        return new RedirectResponse($targetPath);
    }

    private function getDefaultSuccess(Request $request): string
    {
        return $this->urlGenerator->generate('park_manager.user.home');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

    private function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('park_manager.user.security_login');
    }

    public function supports(Request $request): bool
    {
        return $request->request->has('_email');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function supportsRememberMe(): bool
    {
        return true;
    }
}
