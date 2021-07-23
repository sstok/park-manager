<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Infrastructure\DependencyInjection\Compiler\DomainModelsResolverPass;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\Tests\Mock\DomainModels\ByteSize;
use ParkManager\Tests\Mock\DomainModels\Owner;
use ParkManager\Tests\Mock\DomainModels\OwnerId;
use ParkManager\Tests\Mock\DomainModels\OwnerRepository;
use ParkManager\Tests\Mock\DomainModels\ReportPeriod;
use ParkManager\Tests\Mock\DomainModels\Webhosting\Ftp\FtpUser;
use ParkManager\Tests\Mock\DomainModels\Webhosting\Ftp\FtpUserId;
use ParkManager\Tests\Mock\DomainModels\Webhosting\Ftp\FtpUserRepository;
use ParkManager\Tests\Mock\DomainModels\Webhosting\Space\Space;
use ParkManager\Tests\Mock\DomainModels\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\DomainModels\Webhosting\Space\SpaceRepository;
use ParkManager\UI\Web\ArgumentResolver\ModelResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class DomainModelsResolverPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->register(ModelResolver::class, ModelResolver::class);
        $container->register(RepositoryLocator::class, RepositoryLocator::class);
        $container->register(EntityRenderer::class, EntityRenderer::class);

        $container->addCompilerPass(
            new DomainModelsResolverPass(
                \dirname(__DIR__, 3) . '/Mock/DomainModels',
                'ParkManager\\Tests\\Mock\\DomainModels'
            )
        );
    }

    /** @test */
    public function it_sets_services_arguments(): void
    {
        $this->compile();

        $repositoryByEntity = [
            Owner::class => OwnerRepository::class,
            FtpUser::class => FtpUserRepository::class,
            Space::class => SpaceRepository::class,
        ];

        $rootEntityAliases = [
            'owner' => Owner::class,
            'webhosting.ftp.ftp_user' => FtpUser::class,
            'webhosting.space' => Space::class,
        ];

        $idClasses = [
            OwnerId::class => 'fromString',
            FtpUserId::class => 'fromString',
            SpaceId::class => 'fromString',
        ];

        $entityToAlias = [
            Owner::class => 'owner',
            ReportPeriod::class => 'report_period',
            ByteSize::class => 'byte_size',
            FtpUser::class => 'webhosting.ftp.ftp_user',
            Space::class => 'webhosting.space',
        ];

        $this->assertContainerBuilderHasServiceDefinitionWithServiceLocatorArgument(RepositoryLocator::class, 0, $repositoryByEntity);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(RepositoryLocator::class, 1, $rootEntityAliases);

        $this->assertContainerBuilderHasServiceDefinitionWithServiceLocatorArgument(ModelResolver::class, 0, $repositoryByEntity);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(ModelResolver::class, 1, $idClasses);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EntityRenderer::class, 2, $entityToAlias);
    }
}
