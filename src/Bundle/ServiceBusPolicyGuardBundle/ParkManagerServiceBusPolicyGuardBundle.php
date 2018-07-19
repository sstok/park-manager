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

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle;

use ParkManager\Bundle\ServiceBusPolicyGuardBundle\DependencyInjection\Compiler\PolicyGuardConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParkManagerServiceBusPolicyGuardBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PolicyGuardConfigurationPass());
    }
}
