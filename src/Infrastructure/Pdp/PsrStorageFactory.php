<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Pdp;

use Pdp\ResourceUri;
use Pdp\Storage\PublicSuffixListPsr16Cache;
use Pdp\Storage\PublicSuffixListStorage;
use Pdp\Storage\PublicSuffixListStorageFactory;
use Pdp\Storage\RulesStorage;
use Pdp\Storage\TopLevelDomainListPsr16Cache;
use Pdp\Storage\TopLevelDomainListStorage;
use Pdp\Storage\TopLevelDomainListStorageFactory;
use Pdp\Storage\TopLevelDomainsStorage;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PsrStorageFactory implements ResourceUri, PublicSuffixListStorageFactory, TopLevelDomainListStorageFactory
{
    public function __construct(
        private CacheInterface $cache,
        private HttpClientInterface $client
    ) {
    }

    /**
     * @param mixed $cacheTtl The cache TTL
     */
    public function createPublicSuffixListStorage(string $cachePrefix = '', $cacheTtl = null): PublicSuffixListStorage
    {
        return new RulesStorage(
            new PublicSuffixListPsr16Cache($this->cache, $cachePrefix, $cacheTtl),
            new PublicSuffixListSymfonyClient($this->client)
        );
    }

    /**
     * @param mixed $cacheTtl The cache TTL
     */
    public function createTopLevelDomainListStorage(string $cachePrefix = '', $cacheTtl = null): TopLevelDomainListStorage
    {
        return new TopLevelDomainsStorage(
            new TopLevelDomainListPsr16Cache($this->cache, $cachePrefix, $cacheTtl),
            new TopLevelDomainListSymfonyClient($this->client)
        );
    }
}
