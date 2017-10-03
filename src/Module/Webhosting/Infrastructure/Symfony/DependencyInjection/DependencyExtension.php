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

namespace ParkManager\Module\Webhosting\Infrastructure\Symfony\DependencyInjection;

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
    public const EXTENSION_ALIAS = 'webhosting';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/services/'));
        $loader->load('core.php');
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependProophConfig($container);
    }

    private function prependProophConfig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('prooph_service_bus', [
            'command_buses' => ['webhosting.command_bus' => []],
            'query_buses' => [
                'webhosting.query_bus' => [],
            ],
            'event_buses' => [
                'webhosting.event_bus' => [
                    'plugins' => ['prooph_service_bus.on_event_invoke_strategy'],
                ],
            ],
        ]);
    }
}
