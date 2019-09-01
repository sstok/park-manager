<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\DBAL\Types\Type;
use ParkManager\Bundle\WebhostingBundle\DependencyInjection\Compiler\CapabilitiesPass;
use ParkManager\Bundle\WebhostingBundle\DependencyInjection\DependencyExtension;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Package\WebhostingCapabilitiesType;
use ParkManager\Bundle\WebhostingBundle\Package\CapabilitiesFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use function dirname;

final class ParkManagerWebhostingModule extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new DependencyExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        $path = $this->getPath() . '/src/Model/';
        $container->addCompilerPass(DoctrineOrmMappingsPass::createAnnotationMappingDriver([$path => $this->getNamespace() . '\\Model'], [$path]));

        $container->addCompilerPass(new CapabilitiesPass());
    }

    public function boot(): void
    {
        if (! Type::hasType('webhosting_capabilities')) {
            Type::addType('webhosting_capabilities', WebhostingCapabilitiesType::class);
        }

        /** @var WebhostingCapabilitiesType $type */
        $type = Type::getType('webhosting_capabilities');
        $type->setCapabilitiesFactory($this->container->get(CapabilitiesFactory::class));
    }

    public function shutdown(): void
    {
        if (Type::hasType('webhosting_capabilities')) {
            /** @var WebhostingCapabilitiesType $type */
            $type = Type::getType('webhosting_capabilities');
            $type->setCapabilitiesFactory(null);
        }
    }
}
