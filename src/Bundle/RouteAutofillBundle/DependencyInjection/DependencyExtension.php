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

namespace ParkManager\Bundle\RouteAutofillBundle\DependencyInjection;

use ParkManager\Bundle\RouteAutofillBundle\CacheWarmer\RouteRedirectMappingWarmer;
use ParkManager\Bundle\RouteAutofillBundle\EventListener\RouteRedirectResponseListener;
use ParkManager\Bundle\RouteAutofillBundle\MappingFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class DependencyExtension extends Extension
{
    public const EXTENSION_ALIAS = 'park_manager_route_autofill';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register(RouteRedirectMappingWarmer::class)
            ->addArgument(new Reference('router.default'))
            ->addTag('kernel.cache_warmer');

        $container->register(RouteRedirectResponseListener::class)
            ->setArgument(
                1,
                (new Definition(MappingFileLoader::class))->setArguments(['%kernel.cache_dir%/route_autofill_mapping.php'])
            );
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }
}
