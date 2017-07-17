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

namespace ParkManager\Bundle\UserBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use ParkManager\Bundle\UserBundle\DependencyInjection\DependencyExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class ParkManagerUserBundle extends Bundle
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
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver([
                realpath(__DIR__.'/Resources/config/Doctrine') => 'ParkManager\\Component\\User\\Model',
            ])
        );
    }

    protected function getContainerExtensionClass(): string
    {
        return DependencyExtension::class;
    }
}
