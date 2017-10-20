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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class Configuration implements ConfigurationInterface
{
    private $configName;

    public function __construct(string $configName = DependencyExtension::EXTENSION_ALIAS)
    {
        $this->configName = $configName;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->configName);

        $rootNode
            ->children()
                ->arrayNode('capability')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('command_mapping')
                            ->useAttributeAsKey('name')
                            ->prototype('array')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
