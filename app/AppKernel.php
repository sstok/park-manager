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

use ParkManager\SharedKernel\Infrastructure\Symfony\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Default AppKernel.
 */
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // Put here your own bundles!
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        }

        return array_merge(parent::registerBundles(), $bundles);
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $rootDir = $this->getRootDir();
        if (!is_file($file = $rootDir.'/config/config_'.$this->environment.'.yml')) {
            $file = $rootDir.'/config/config_'.$this->environment.'.dist.yml';
        }

        $loader->load($file);
    }
}
