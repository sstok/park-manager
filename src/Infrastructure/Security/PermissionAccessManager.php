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

/**
 * @final
 */
class PermissionAccessManager
{
    /**
     * @param array<string, class-string> $permissionsShortNames
     */
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ContainerInterface $deciders,
        private array $permissionsShortNames
    ) {
    }

    public function decide(Permission $permission, ?TokenInterface $token = null): int
    {
        if ($token === null) {
            $token = $this->tokenStorage->getToken();
        }

        if ($token === null || ! $token->getUser() instanceof SecurityUser) {
            return PermissionDecider::DECIDE_DENY;
        }

        $user = $token->getUser();
        \assert($user instanceof SecurityUser);

        if ($permission instanceof PermissionExpression) {
            $permission = $this->resolvePermissionExpression($permission);
        }

        if ($permission instanceof SelfDecidingPermission) {
            return $permission->__invoke($token, $user, $this);
        }

        $class = $this->resolvePermissionName($permission);

        if (! $this->deciders->has($class)) {
            throw new RuntimeException(sprintf('No Decider is registered for Permission "%s".', $class));
        }

        $decider = $this->deciders->get($class);
        \assert($decider instanceof PermissionDecider);

        return $decider->decide($permission, $token, $user, $this);
    }

    private function resolvePermissionName(Permission $permission): string
    {
        $class = $permission::class;

        if ($permission instanceof AliasedPermission) {
            $class = $permission->getAlias();
        }

        return ltrim($class, '\\');
    }

    private function resolvePermissionExpression(PermissionExpression $permission): Permission
    {
        if (str_contains($permission->name, '\\')) {
            return new $permission->name(...$permission->arguments);
        }

        if (isset($this->permissionsShortNames[$permission->name])) {
            return new $this->permissionsShortNames[$permission->name](...$permission->arguments);
        }

        $name = $permission->name;
        $candidates = [];

        foreach (array_keys($this->permissionsShortNames) as $shortName) {
            if (str_contains($shortName, $name) || (levenshtein($name, $shortName) <= mb_strlen($name) / 3)) {
                $candidates[] = $shortName;
            }
        }

        if ($candidates) {
            sort($candidates);

            $message = sprintf("\nDid you e.g. mean \"%s\"", implode('", "', $candidates));
        } else {
            $message = sprintf("\nSupported \"%s\"", implode('", "', array_keys($this->permissionsShortNames)));
        }

        throw new RuntimeException(sprintf('No Permission can be found for short-name "%s".', $permission->name) . $message);
    }
}
