<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Infrastructure\DependencyInjection\Compiler\PermissionShortAliasPass;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ParkManager\Tests\Mock\Infrastructure\Security\Permission\IsSuperAdmin;
use ParkManager\Tests\Mock\Infrastructure\Security\Permission\Webhosting\Ftp\FtpUser;
use ParkManager\Tests\Mock\Infrastructure\Security\Permission\Webhosting\IsSpaceOwner;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class PermissionShortAliasPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $this->registerService(PermissionAccessManager::class, PermissionAccessManager::class);

        $container->addCompilerPass(
            new PermissionShortAliasPass(
                \dirname(__DIR__, 3) . '/Mock/Infrastructure/Security/Permission',
                'ParkManager\\Tests\\Mock\\Infrastructure\\Security\\Permission'
            )
        );
    }

    /** @test */
    public function it_sets_the_alias_argument(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PermissionAccessManager::class, 2, [
            'is_super_admin' => IsSuperAdmin::class,
            'webhosting.is_space_owner' => IsSpaceOwner::class,
            'webhosting.ftp.ftp_user' => FtpUser::class,
        ]);
    }
}
