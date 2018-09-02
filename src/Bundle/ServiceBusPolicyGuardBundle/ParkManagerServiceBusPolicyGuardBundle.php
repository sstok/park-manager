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
