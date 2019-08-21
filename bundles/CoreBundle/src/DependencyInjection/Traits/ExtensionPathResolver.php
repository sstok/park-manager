<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\DependencyInjection\Traits;

use LogicException;
use ReflectionClass;

trait ExtensionPathResolver
{
    /** @var string|null */
    protected $bundleNamespace;

    /** @var string|null */
    protected $bundlePath;

    final protected function initBundlePath(): void
    {
        if ($this->bundlePath === null) {
            $r = new ReflectionClass(static::class);
            $namespace = $r->getNamespaceName();

            if (substr($namespace, -20) !== '\\DependencyInjection') {
                throw new LogicException(sprintf('The namespace "%s" is expected to end with "\\DependencyInjection".', $namespace));
            }

            $this->bundleNamespace = substr($namespace, 0, -20);
            $this->bundlePath      = realpath(dirname($r->getFileName(), 3));
        }
    }
}
