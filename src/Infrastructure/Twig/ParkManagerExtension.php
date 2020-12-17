<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class ParkManagerExtension extends AbstractExtension
{
    private TranslatorInterface $translator;
    private TokenStorageInterface $tokenStorage;
    private UserRepository $userRepository;

    public function __construct(TranslatorInterface $translator, TokenStorageInterface $tokenStorage, UserRepository $userRepository)
    {
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_safe', [$this, 'trans'], ['needs_environment' => true, 'is_safe' => ['all']]),
            new TwigFilter('merge_attr_class', [$this, 'mergeAttrClass']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_current_user', [$this, 'getCurrentUser']),
        ];
    }

    public function trans(Environment $env, string $message, array $arguments = [], ?string $domain = null, ?string $locale = null, ?int $count = null): string
    {
        if ($count !== null) {
            $arguments['%count%'] = $count;
        }

        foreach ($arguments as $name => $value) {
            $arguments[$name] = twig_escape_filter($env, $value);
        }

        return $this->translator->trans($message, $arguments, $domain, $locale);
    }

    public function mergeAttrClass(array $attributes, string $class, bool $append = false): array
    {
        if (! isset($attributes['class'])) {
            $attributes['class'] = '';
        }

        if ($append) {
            $attributes['class'] .= ' ' . $class;
        } else {
            $attributes['class'] = $class . ' ' . $attributes['class'];
        }

        $attributes['class'] = \trim($attributes['class']);

        return $attributes;
    }

    public function getCurrentUser(): User
    {
        static $currentToken, $currentUser;

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            throw new AccessDeniedException();
        }

        if ($currentToken !== $token) {
            $currentToken = $token;
            $currentUser = $this->userRepository->get(UserId::fromString($token->getUsername()));
        }

        return $currentUser;
    }
}

// Force autoloading of the EscaperExtension as we need the twig_escape_filter() function
\class_exists(EscaperExtension::class);
