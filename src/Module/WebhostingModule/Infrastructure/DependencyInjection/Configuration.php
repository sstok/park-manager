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

namespace ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function is_string;

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
        $rootNode    = $treeBuilder->root($this->configName);

        $rootNode
            ->children()
                ->append($this->addCapabilities())
            ->end();

        return $treeBuilder;
    }

    private function addCapabilities()
    {
        $node = (new TreeBuilder())->root('capabilities');
        $node
            ->canBeDisabled()
            ->children()
                ->arrayNode('mapping')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('command')
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static function ($v) { return ['capability' => $v]; })
                        ->end()
                        ->children()
                            ->scalarNode('capability')->cannotBeEmpty()->end()
                            ->arrayNode('attributes')
                                ->useAttributeAsKey('key')
                                ->defaultValue([])
                                ->scalarPrototype()
                                    ->validate()
                                        ->ifTrue(static function ($v) { return ! is_string($v); })
                                        ->thenInvalid('Attribute value expected to a property path as string.')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
