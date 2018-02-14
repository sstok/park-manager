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

namespace ParkManager\Bundle\CoreBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use ParkManager\Bridge\Doctrine\Type\ArrayCollectionType;
use ParkManager\Bundle\CoreBundle\DependencyInjection\DependencyExtension;
use ParkManager\Bundle\UserBundle\DependencyInjection\Compiler\UserFormHandlerPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class ParkManagerCoreBundle extends Bundle
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
        $dirname = dirname((new \ReflectionClass(ArrayCollectionType::class))->getFileName(), 2);

        $container->addCompilerPass(
            new UserFormHandlerPass('park_manager.form_handler.administrator.handler_registry', 'admin_form.handler'),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver([
                realpath($dirname.'/Resources/Mapping/Security') => 'ParkManager\\Component\\Security',
                realpath(__DIR__.'/Resources/config/Doctrine/Administrator') => 'ParkManager\\Component\\Core\\Model\\Administrator',
            ])
        );
    }

    public function registerCommands(Application $application)
    {
        // Noop
    }

    protected function getContainerExtensionClass(): string
    {
        return DependencyExtension::class;
    }
}
