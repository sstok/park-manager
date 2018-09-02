<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\RouteAutofillBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

final class RouteRedirectMappingWarmer extends CacheWarmer
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * This cache warmer is not optional, missing the RouteRedirect Mappings
     * will cause a fatal error on routes that expect this functionality.
     *
     * @return false
     */
    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir): void
    {
        $mapping = [];

        /** @var Route $route */
        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            if (!$route->hasOption('autofill_variables')) {
                continue;
            }

            $compiledRoute = $route->compile();
            // Use a boolean value to speed-up the array look-up process
            $mapping[$routeName] = array_fill_keys($compiledRoute->getVariables(), true);
        }

        $this->writeCacheFile($cacheDir.'/route_autofill_mapping.php', sprintf("<?php return %s;\n", var_export($mapping, true)));
    }
}
