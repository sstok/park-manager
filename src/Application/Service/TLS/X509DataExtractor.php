<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use Carbon\Carbon;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\TLS\Violation\UnprocessablePEM;

final class X509DataExtractor
{
    private ?string $hash = null;
    private ?array $fields = null;

    /**
     * @return array<string,mixed> Note that normalized data key's start with an underscore
     */
    public function extractRawData(string $contents, string $name = '', bool $withPublicKey = false): array
    {
        $hash = \hash('sha256', $contents);

        // The same cert information is likely to be validated multiple times
        // So keep a local cache to speed-up the parsing process a little.
        if ($hash === $this->hash && isset($this->fields)) {
            return $this->fields;
        }

        $x509Read = @\openssl_x509_read($contents);

        if ($x509Read === false) {
            throw new UnprocessablePEM($name, $contents);
        }

        // @codeCoverageIgnoreStart
        $rawData = @\openssl_x509_parse($x509Read, false);

        if ($rawData === false) {
            throw new UnprocessablePEM($name, $contents);
        }
        // @codeCoverageIgnoreEnd

        try {
            $fingerprint = \openssl_x509_fingerprint($x509Read, $rawData['signatureTypeSN']) ?: '';
        } catch (\Throwable $e) {
            $fingerprint = '';
        }

        if ($withPublicKey) {
            $pubKeyRead = \openssl_pkey_get_public($x509Read);

            if ($pubKeyRead === false) {
                throw new UnprocessablePEM($name, $contents);
            }

            $pubKey = \openssl_pkey_get_details($pubKeyRead);

            \openssl_pkey_free($pubKeyRead);
            \openssl_x509_free($x509Read);
        } else {
            $pubKey = null;
        }

        $altNames = $this->getAltNames($rawData);
        $rawData += [
            '_commonName' => \trim($rawData['subject']['commonName']),
            '_altNames' => $altNames,
            '_emails' => $altNames['rfc822'] ?? [],
            '_signatureAlgorithm' => $rawData['signatureTypeSN'],
            '_fingerprint' => $fingerprint,
            '_validTo' => Carbon::rawParse($rawData['validTo_time_t']),
            '_validFrom' => Carbon::rawParse($rawData['validFrom_time_t']),
            '_pubKey' => $pubKey['key'] ?? '',
        ];

        $rawData['_domains'] = \array_merge($rawData['_altNames']['dns'] ?? [], $rawData['_altNames']['ip address'] ?? []);
        $rawData['_alt_domains'] = $rawData['_domains'];
        $rawData['_domains'][] = $rawData['_commonName'];

        // Remove any duplicates and ensure the keys are incremental.
        $rawData['_domains'] = \array_unique($rawData['_domains']);

        $this->hash = $hash;
        $this->fields = $rawData;

        return $rawData;
    }

    /**
     * @param array<string,mixed> $rawData
     *
     * @return array<string, array<int, string>>
     */
    private function getAltNames(array $rawData): array
    {
        if (! isset($rawData['extensions']['subjectAltName'])) {
            return [];
        }

        $altNames = [];

        foreach (\explode(',', $rawData['extensions']['subjectAltName']) as $altName) {
            [$type, $value] = \explode(':', \trim($altName), 2);
            $altNames[\mb_strtolower($type)][] = $value;
        }

        return $altNames;
    }

    /**
     * @return array<string,mixed>
     */
    public function getPrivateKeyDetails(HiddenString $privateKey): array
    {
        $key = $privateKey->getString();
        $r = null;

        try {
            $r = \openssl_pkey_get_private($key);

            // Note that the KeyValidator will already check if the key is in-fact valid.
            // This failure will only happen in exceptional situations.
            if ($r === false) {
                throw new \RuntimeException('Unable to read private key-data, invalid key provided?');
            }

            // @codeCoverageIgnoreStart
            $details = \openssl_pkey_get_details($r);

            if ($details === false) {
                throw new \RuntimeException('Unable to read private key-data. Unknown error.');
            }
        } finally {
            if (\is_resource($r)) {
                @\openssl_pkey_free($r);
            }

            \sodium_memzero($key);
        }
        // @codeCoverageIgnoreEnd

        return [
            'bits' => $details['bits'],
            'type' => $details['type'],
        ];
    }
}
