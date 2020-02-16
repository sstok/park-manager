<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @final
 */
class PermissionAccessManager
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var ContainerInterface */
    private $deciders;

    /** @var array */
    private $permissionsShortNames;

    public function __construct(TokenStorageInterface $tokenStorage, ContainerInterface $deciders, array $permissionsShortNames)
    {
        $this->tokenStorage = $tokenStorage;
        $this->deciders = $deciders;
        $this->permissionsShortNames = $permissionsShortNames;
    }

    public function decide(Permission $permission, ?TokenInterface $token = null): int
    {
        if ($token === null) {
            $token = $this->tokenStorage->getToken();
        }

        if ($token === null || ! $token->isAuthenticated() || ! $token->getUser() instanceof SecurityUser) {
            return PermissionDecider::DECIDE_DENY;
        }

        if ($permission instanceof PermissionExpression) {
            $permission = $this->resolvePermissionExpression($permission);
        }

        if ($permission instanceof SelfDecidingPermission) {
            return $permission($token, $token->getUser(), $this);
        }

        $class = $this->resolvePermissionName($permission);

        if (! $this->deciders->has($class)) {
            throw new RuntimeException(\sprintf('No Decider is registered for Permission "%s".', $class));
        }

        /** @var PermissionDecider $decider */
        $decider = $this->deciders->get($class);

        return $decider->decide($permission, $token, $token->getUser(), $this);
    }

    private function resolvePermissionName(Permission $permission): string
    {
        $class = \get_class($permission);

        if ($permission instanceof AliasedPermission) {
            $class = $permission->getAlias();
        }

        return ltrim($class, '\\');
    }

    private function resolvePermissionExpression(PermissionExpression $permission): Permission
    {
        if (strpos($permission->name, '\\') !== false) {
            return new $permission->name(...$permission->arguments);
        }

        if (isset($this->permissionsShortNames[$permission->name])) {
            return new $this->permissionsShortNames[$permission->name](...$permission->arguments);
        }

        $name = $permission->name;
        $candidates = [];

        foreach (array_keys($this->permissionsShortNames) as $shortName) {
            if (strpos($shortName, $name) !== false || (levenshtein($name, $shortName) <= \strlen($name) / 3)) {
                $candidates[] = $shortName;
            }
        }

        if ($candidates) {
            sort($candidates);

            $message = sprintf("\nDid you e.g. mean \"%s\"", implode('", "', $candidates));
        } else {
            $message = sprintf("\nSupported \"%s\"", implode('", "', array_keys($this->permissionsShortNames)));
        }

        throw new RuntimeException(\sprintf('No Permission can be found for short-name "%s".', $permission->name) . $message);
    }
}
