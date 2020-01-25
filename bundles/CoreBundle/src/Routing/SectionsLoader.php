<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;

final class SectionsLoader extends Loader
{
    private $loader;

    /**
     * @param LoaderResolverInterface $loader Route loader resolver
     */
    public function __construct(LoaderResolverInterface $loader)
    {
        $this->loader = $loader;
    }

    public function load($resource, string $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->loadAdminSection());
        $collection->addCollection($this->loadApiSection());
        $collection->addCollection($this->loadResource('park_manager.client_section.root'));

        return $collection;
    }

    public function supports($resource, string $type = null): bool
    {
        return $type === 'park_manager_sections_loader';
    }

    private function loadResource(string $resource): RouteCollection
    {
        $loader = $this->loader->resolve($resource, 'rollerworks_autowiring');
        $collection = $loader->load($resource, 'rollerworks_autowiring');
        \assert($collection instanceof RouteCollection);

        $collection->setSchemes(['https']);

        return $collection;
    }

    private function loadAdminSection(): RouteCollection
    {
        $admin = $this->loadResource('park_manager.admin_section.root');
        $admin->addPrefix('admin/');

        return $admin;
    }

    private function loadApiSection(): RouteCollection
    {
        $api = $this->loadResource('park_manager.api_section.root');
        $api->addPrefix('api/');

        return $api;
    }
}
