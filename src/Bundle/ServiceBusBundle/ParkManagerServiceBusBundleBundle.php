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

namespace ParkManager\Bundle\ServiceBusBundle;

use ParkManager\Bridge\ServiceBus\DependencyInjection\Compiler\MessageBusPass;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Compiler\MessageGuardPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class ParkManagerServiceBusBundleBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MessageBusPass());
        $container->addCompilerPass(new MessageGuardPass(), PassConfig::TYPE_BEFORE_REMOVING, 1);
    }
}
