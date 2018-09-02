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

namespace ParkManager\Bundle\ServiceBusBundle;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\DomainEventsEmitterPass;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\MessageBusPass;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\MessageGuardPass;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\TracingDomainEventsEmitterPass;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\DependencyExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParkManagerServiceBusBundle extends Bundle
{
    public function getContainerExtension(): DependencyExtension
    {
        if (null === $this->extension) {
            $this->extension = new DependencyExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DomainEventsEmitterPass(), PassConfig::TYPE_BEFORE_REMOVING, 1);
        $container->addCompilerPass(new MessageGuardPass(), PassConfig::TYPE_BEFORE_REMOVING, 1);
        $container->addCompilerPass(new MessageBusPass());

        if ($container->getParameter('kernel.debug')) {
            $container->addCompilerPass(new TracingDomainEventsEmitterPass());
        }
    }

    protected function getContainerExtensionClass(): string
    {
        return DependencyExtension::class;
    }
}
