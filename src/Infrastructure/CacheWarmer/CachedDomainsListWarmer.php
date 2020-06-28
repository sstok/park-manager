<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\CacheWarmer;

use Pdp\Manager;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Refresh domains public-prefix list on cache warm-up.
 */
final class CachedDomainsListWarmer implements CacheWarmerInterface
{
    private Manager $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp(string $cacheDir): void
    {
        $this->manager->refreshRules();
    }
}
