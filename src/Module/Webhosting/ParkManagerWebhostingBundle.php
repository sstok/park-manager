<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\DBAL\Types\Type;
use ParkManager\Module\Webhosting\Domain\Package\CapabilitiesFactory;
use ParkManager\Module\Webhosting\Infrastructure\DependencyInjection\Compiler\{
    CapabilitiesRegistryPass, CommandToCapabilitiesGuardPass
};
use ParkManager\Module\Webhosting\Infrastructure\DependencyInjection\DependencyExtension;
use ParkManager\Module\Webhosting\Infrastructure\Doctrine\Package\WebhostingCapabilitiesType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ParkManagerWebhostingBundle extends Bundle
{
    public function getContainerExtension(): DependencyExtension
    {
        if (null === $this->extension) {
            $this->extension = new DependencyExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        $mappings = [
            realpath(__DIR__.'/Infrastructure/Doctrine/Account/Mapping') => 'ParkManager\Module\Webhosting\Domain\Account',
            realpath(__DIR__.'/Infrastructure/Doctrine/DomainName/Mapping') => 'ParkManager\Module\Webhosting\Domain\DomainName',
            realpath(__DIR__.'/Infrastructure/Doctrine/Package/Mapping') => 'ParkManager\Module\Webhosting\Domain\Package',
            realpath(__DIR__.'/Infrastructure/Doctrine/RootMapping') => 'ParkManager\Module\Webhosting\Domain',
        ];

        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver($mappings));
        $container->addCompilerPass(new CapabilitiesRegistryPass());
    }

    public function boot(): void
    {
        if (!Type::hasType('webhosting_capabilities')) {
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
