<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\DependencyInjection\Compiler;

use Doctrine\ORM\Mapping\Embeddable;
use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Domain\EnumEqualityTrait;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\UI\Web\ArgumentResolver\ModelResolver;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Throwable;

final class DomainModelsResolverPass implements CompilerPassInterface
{
    private const NAMESPACE_PREFIX = 'ParkManager\\Domain';

    public function __construct(
        private string $directory,
        private string $namespacePrefix = self::NAMESPACE_PREFIX
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $finder = new Finder();
        $finder
            ->ignoreDotFiles(true)
            ->in($this->directory)
            ->exclude('Exception')
            ->exclude('Translation')
            ->exclude('Webhosting/SubDomain/TLS') // This cannot be loaded with a repository
            ->name('{\.php$}')
        ;

        $typeFilter = static function (string $class): bool {
            if (! class_exists($class)) {
                return false;
            }

            $implements = class_implements($class);

            if (\in_array(Throwable::class, $implements, true)) {
                return false;
            }

            $r = new ReflectionClass($class);

            if ($r->isAbstract()) {
                return false;
            }

            // Cannot alias Embeddables.
            if (\count($r->getAttributes(Embeddable::class)) > 0) {
                return false;
            }

            // Don't include traits.
            return ! \in_array(EnumEqualityTrait::class, $r->getTraitNames(), true);
        };

        $classes = AliasResolver::findFiles($finder, $this->namespacePrefix, $typeFilter);

        $repositoryByEntity = [];
        $rootEntityAliases = [];
        $entityToAlias = [];
        $idClasses = [];

        foreach ($classes as $className) {
            if (str_ends_with($className, 'Id')) {
                $idClasses[$className] = 'fromString';

                continue;
            }

            $alias = AliasResolver::getClassAlias($className, $this->namespacePrefix);
            $entityToAlias[$className] = $alias;

            if (interface_exists($className . 'Repository')) {
                $repositoryByEntity[$className] = new Reference($className . 'Repository');
                $rootEntityAliases[$alias] = $className;
            }
        }

        $container->findDefinition(RepositoryLocator::class)
            ->setArgument(0, ServiceLocatorTagPass::register($container, $repositoryByEntity, RepositoryLocator::class))
            ->setArgument(1, $rootEntityAliases)
        ;

        $container->findDefinition(ModelResolver::class)
            ->setArgument(0, ServiceLocatorTagPass::register($container, $repositoryByEntity, ModelResolver::class))
            ->setArgument(1, $idClasses)
        ;

        $container->findDefinition(EntityRenderer::class)
            ->setArgument(2, $entityToAlias)
        ;
    }
}
