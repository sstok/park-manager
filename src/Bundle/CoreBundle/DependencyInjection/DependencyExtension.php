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

namespace ParkManager\Bundle\CoreBundle\DependencyInjection;

use ParkManager\Bridge\Doctrine\Type\ArrayCollectionType;
use ParkManager\Component\Core\Model\Command\RegisterAdministrator;
use ParkManager\Component\User\Model\Command\{
    ChangeUserPassword,
    ConfirmUserPasswordReset,
    RequestUserPasswordReset
};
use ParkManager\Component\User\Model\Event\UserPasswordWasChanged;
use ParkManager\Component\User\Model\Query\GetUserByPasswordResetToken;
use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\SectioningFactory;
use Rollerworks\Bundle\RouteAutowiringBundle\RouteImporter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DependencyExtension extends Extension implements PrependExtensionInterface
{
    public const EXTENSION_ALIAS = 'park_manager';

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $this->registerApplicationSections($container, $config);
        $this->registerRoutes($container);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineConfig($container);
        $this->prependProophConfig($container);

        $container->prependExtensionConfig('twig', [
            'paths' => [realpath(dirname(__DIR__).'/templates') => 'ParkManager'],
        ]);
    }

    private function prependDoctrineConfig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => ['array_collection' => ['class' => ArrayCollectionType::class, 'commented' => true]],
            ],
        ]);
    }

    private function prependProophConfig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('prooph_service_bus', [
            'command_buses' => [
                'administrator.command_bus' => [
                    'router' => [
                        'type' => 'prooph_service_bus.command_bus_router',
                        'routes' => [
                            RequestUserPasswordReset::class => 'park_manager.command_handler.request_administrator_password_reset',
                            ConfirmUserPasswordReset::class => 'park_manager.command_handler.confirm_administrator_password_reset',
                            ChangeUserPassword::class => 'park_manager.command_handler.change_administrator_password',
                            RegisterAdministrator::class => 'park_manager.command_handler.register_administrator',
                        ],
                    ],
                ],
            ],
            'query_buses' => [
                'administrator.query_bus' => [
                    'router' => [
                        'type' => 'prooph_service_bus.query_bus_router',
                        'routes' => [
                            GetUserByPasswordResetToken::class => 'park_manager.query_handler.get_administrator_by_password_reset_token',
                        ],
                    ],
                ],
            ],
            'event_buses' => [
                'administrator.event_bus' => [
                    'plugins' => ['prooph_service_bus.on_event_invoke_strategy'],
                    'router' => [
                        'type' => 'prooph_service_bus.event_bus_router',
                        'routes' => [
                            UserPasswordWasChanged::class => ['park_manager.domain_event_listener.update_auth_token_when_password_was_changed.administrator'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function registerApplicationSections(ContainerBuilder $container, array $config): void
    {
        $factory = new SectioningFactory($container, 'park_manager.section');

        foreach ($config['sections'] as $section => $sectionConfig) {
            $factory->set($section, $sectionConfig);
        }
    }

    private function registerRoutes(ContainerBuilder $container): void
    {
        $routeImporter = new RouteImporter($container);
        $routeImporter->addObjectResource($this);
        $routeImporter->import('@ParkManagerCoreBundle/Resources/config/routing/administrator.yaml', 'park_manager.admin_section.root');
    }
}
