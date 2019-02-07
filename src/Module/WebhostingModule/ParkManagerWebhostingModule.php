<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule;

use Doctrine\DBAL\Types\Type;
use ParkManager\Module\CoreModule\Infrastructure\DependencyInjection\Module\AbstractParkManagerModule;
use ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection\Compiler\CapabilitiesPass;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Package\WebhostingCapabilitiesType;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function realpath;

final class ParkManagerWebhostingModule extends AbstractParkManagerModule
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

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

    protected function getDoctrineOrmMappings(): array
    {
        $mapping = parent::getDoctrineOrmMappings();

        $mapping[realpath(__DIR__ . '/Infrastructure/Doctrine/RootMapping')] = 'ParkManager\Module\WebhostingModule\Domain';

        return $mapping;
    }
}
