<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager;

use ParkManager\Infrastructure\DependencyInjection\Compiler\PermissionDeciderPass;
use ParkManager\Infrastructure\DependencyInjection\Compiler\PermissionShortAliasPass;
use ParkManager\Infrastructure\Security\PermissionDecider;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader): void
    {
        $container->import('../config/packages/*.yaml');
        $container->import('../config/packages/' . $this->environment . '/*.yaml');
        $container->import('../config/services.php');
        $container->import('../config/{services}_' . $this->environment . '.php');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/' . $this->environment . '/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
        $routes->import('../config/routes.php')->schemes(['https']);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PermissionDeciderPass());
        $container->addCompilerPass(new PermissionShortAliasPass());
        $container->registerForAutoconfiguration(PermissionDecider::class)
            ->addTag('park_manager.security.permission_decider'); // Needs CompilerPass
    }
}
