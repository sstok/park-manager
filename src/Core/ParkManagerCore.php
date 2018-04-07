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

namespace ParkManager\Core;

use ParkManager\Bridge\Doctrine\Type\ArrayCollectionType;
use ParkManager\Bundle\UserBundle\DependencyInjection\Compiler\UserFormHandlerPass;
use ParkManager\Component\Module\AbstractParkManagerModule;
use ParkManager\Core\Infrastructure\DependencyInjection\DependencyExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class ParkManagerCore extends AbstractParkManagerModule
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
        parent::build($container);

        $container->addCompilerPass(
            new UserFormHandlerPass('park_manager.form_handler.administrator.handler_registry', 'admin_form.handler'),
            PassConfig::TYPE_BEFORE_REMOVING
        );
    }

    protected function getDoctrineMappings(): array
    {
        $mapping = parent::getDoctrineMappings();
        $mapping[realpath(
            \dirname((new \ReflectionClass(ArrayCollectionType::class))->getFileName(), 2).
            '/Resources/Mapping/Security'
        )] = 'ParkManager\\Component\\Security';

        return $mapping;
    }

    protected function getContainerExtensionClass(): string
    {
        return DependencyExtension::class;
    }
}
