<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock;

use InvalidArgumentException;
use ParkManager\Application\Service\PdpManager;
use ParkManager\Infrastructure\Pdp\PsrStorageFactory;
use Pdp\ResourceUri;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PdpMockProvider
{
    private static ?PdpManager $pdpManager = null;

    public static function getPdpManager(): PdpManager
    {
        if (self::$pdpManager !== null) {
            return self::$pdpManager;
        }

        $httpClient = new MockHttpClient(
            static function ($method, $url): MockResponse {
                if ($url === ResourceUri::PUBLIC_SUFFIX_LIST_URI) {
                    return new MockResponse(file_get_contents(__DIR__ . '/../Fixtures/public_suffix_list.dat'));
                }

                if ($url === ResourceUri::TOP_LEVEL_DOMAIN_LIST_URI) {
                    return new MockResponse(file_get_contents(__DIR__ . '/../Fixtures/tlds-alpha-by-domain.txt'));
                }

                throw new InvalidArgumentException(sprintf('Unknown url %s requested.', $url));
            }
        );
        $factory = new PsrStorageFactory(
            new Psr16Cache(new PhpFilesAdapter('pdp-parkmanager-test', 0, null, true)), $httpClient
        );

        return self::$pdpManager = new PdpManager(
            $factory->createPublicSuffixListStorage(),
            $factory->createTopLevelDomainListStorage()
        );
    }
}
