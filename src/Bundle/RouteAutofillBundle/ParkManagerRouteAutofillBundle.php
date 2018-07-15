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

namespace ParkManager\Bundle\RouteAutofillBundle;

use ParkManager\Bundle\RouteAutofillBundle\DependencyInjection\DependencyExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParkManagerRouteAutofillBundle extends Bundle
{
    public function getContainerExtension(): DependencyExtension
    {
        if (null === $this->extension) {
            $this->extension = new DependencyExtension();
        }

        return $this->extension;
    }

    protected function getContainerExtensionClass(): string
    {
        return DependencyExtension::class;
    }
}
