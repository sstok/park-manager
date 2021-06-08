<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Pdp;

use Pdp\PublicSuffixList;
use Pdp\Rules;
use Pdp\Storage\PublicSuffixListClient;
use Pdp\UnableToLoadResource;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PublicSuffixListSymfonyClient implements PublicSuffixListClient
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    public function get(string $uri): PublicSuffixList
    {
        try {
            $response = $this->client->request('GET', $uri);
        } catch (TransportException $exception) {
            throw UnableToLoadResource::dueToUnavailableService($uri, $exception);
        }

        if (400 <= $response->getStatusCode()) {
            throw UnableToLoadResource::dueToUnexpectedStatusCode($uri, $response->getStatusCode());
        }

        return Rules::fromString($response->getContent());
    }
}
