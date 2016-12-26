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

namespace ParkManager\SkeletonDancer\Generator;

use ParkManager\SkeletonDancer\Configurator\ComposerConfigurator;
use ParkManager\SkeletonDancer\Configurator\ModuleConfigurator;
use Rollerworks\Tools\SkeletonDancer\Generator;
use Rollerworks\Tools\SkeletonDancer\Service\Filesystem;
use Rollerworks\Tools\SkeletonDancer\StringUtil;

final class ModuleGenerator implements Generator
{
    private $filesystem;
    private $twig;

    public function __construct(\Twig_Environment $twig, Filesystem $filesystem)
    {
        $this->twig = $twig;
        $this->filesystem = $filesystem;
    }

    public function generate(array $configuration)
    {
        $this->filesystem->mkdir(
            [
                $configuration['src_dir_norm'].'Domain',
                $configuration['src_dir_norm'].'Application',
                $configuration['src_dir_norm'].'Infrastructure',
                $configuration['src_dir_norm'].'UI',
                $configuration['src_dir_norm'].'Tests',
            ]
        );

        $this->generateBundle('Infrastructure', 'Infrastructure', $configuration);
        $this->generateBundle('UI', 'Web', $configuration);
    }

    public function getConfigurators()
    {
        return [
            ModuleConfigurator::class,
            ComposerConfigurator::class,
        ];
    }

    private function generateBundle(string $layer, string $bundleTypeName, array $configuration)
    {
        $moduleName = StringUtil::camelize($configuration['module_name']);
        $extensionName = $moduleName.$bundleTypeName;
        $extensionAlias = implode(
            '_',
            [
                StringUtil::underscore($configuration['vendor_prefix']),
                StringUtil::underscore($configuration['module_name']),
                StringUtil::underscore($bundleTypeName),
            ]
        );

        $directory = $configuration['src_dir_norm'].$layer.'/'.$bundleTypeName.'Bundle'.'/';
        $namespace = $configuration['namespace'].'\\'.$layer.'\\'.$bundleTypeName.'Bundle';
        $bundleName = $configuration['vendor_prefix'].$moduleName.$bundleTypeName.'Bundle';

        $this->filesystem->dumpFile(
            $directory.$bundleName.'.php',
            $this->twig->render(
                'SfBundle/bundle.php.twig',
                [
                    'name' => $configuration['name'],
                    'namespace' => $namespace,
                    'bundle' => $bundleName,
                    'extension_name' => $extensionName,
                    'extension_alias' => $extensionAlias,
                ]
            )
        );

        $this->filesystem->dumpFile(
            $directory.'DependencyInjection/'.$extensionName.'Extension.php',
            $this->twig->render(
                'SfBundle/extension.php.twig',
                [
                    'name' => $configuration['name'],
                    'namespace' => $namespace,
                    'extension_name' => $extensionName,
                    'extension_alias' => $extensionAlias,
                    'format' => 'xml',
                ]
            )
        );

        $this->filesystem->dumpFile(
            $directory.'DependencyInjection/Configuration.php',
            $this->twig->render(
                'SfBundle/configuration.php.twig',
                [
                    'namespace' => $namespace,
                ]
            )
        );

        $this->filesystem->dumpFile(
            $directory.'Resources/config/services/core.xml',
            $this->twig->render(
                'SfBundle/services.xml.twig',
                [
                    'namespace' => $configuration['namespace'],
                    'extension_alias' => StringUtil::underscore($configuration['vendor_prefix']).'_'.$configuration['module_name'],
                ]
            )
        );
    }
}
