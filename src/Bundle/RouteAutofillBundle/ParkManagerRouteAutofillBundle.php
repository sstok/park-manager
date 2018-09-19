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

namespace ParkManager\Bundle\RouteAutofillBundle;

use ParkManager\Bundle\RouteAutofillBundle\DependencyInjection\DependencyExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParkManagerRouteAutofillBundle extends Bundle
{
    public function getContainerExtension(): DependencyExtension
    {
        if ($this->extension === null) {
            $this->extension = new DependencyExtension();
        }

        return $this->extension;
    }

    protected function getContainerExtensionClass(): string
    {
        return DependencyExtension::class;
    }
}
