<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use Doctrine\Persistence\ObjectManager;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Halite;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\TLS\Violation\ExpectedLeafCertificate;
use ParkManager\Domain\DomainName\TLS\Certificate;

final class CertificateFactoryImpl implements CertificateFactory
{
    private EncryptionPublicKey $encryptionKey;
    private ObjectManager $objectManager;
    private CAResolver $caResolver;
    private KeyValidator $keyValidator;

    public function __construct(string $encryptionKey, ObjectManager $objectManager, CAResolver $caResolver, KeyValidator $keyValidator = null)
    {
        $this->encryptionKey = new EncryptionPublicKey(new HiddenString($encryptionKey));
        $this->objectManager = $objectManager;
        $this->caResolver = $caResolver;
        $this->keyValidator = $keyValidator ?? new KeyValidator();
    }

    public function createCertificate(string $contents, HiddenString $privateKey, array $caList = []): Certificate
    {
        // Even if the certificate is already stored we still need to make sure the provided private-key matches.
        // Otherwise an attacker might assign the stored key without proper privilege.
        $this->keyValidator->validate($privateKey, $contents);

        /** @var Certificate|null $certificate */
        $certificate = $this->objectManager->find(Certificate::class, Certificate::getHash($contents));

        if ($certificate === null) {
            $certificate = $this->newCertificate($contents, $privateKey, $caList);
        }

        return $certificate;
    }

    /**
     * @param array<string, string> $caList
     */
    private function newCertificate(string $contents, HiddenString $privateKey, array $caList = []): Certificate
    {
        $ca = $this->caResolver->resolve($contents, $caList);
        $fields = $this->extractRawData($contents, $privateKey);

        $privateKeyEncrypted = Crypto::seal($privateKey, $this->encryptionKey, Halite::ENCODE_BASE64);
        $certificate = new Certificate($contents, $privateKeyEncrypted, $fields, $ca);

        $this->objectManager->persist($certificate);

        return $certificate;
    }

    /**
     * @return array<string,mixed>
     */
    private function extractRawData(string $contents, HiddenString $privateKey): array
    {
        $x509Read = \openssl_x509_read($contents);
        $rawData = \openssl_x509_parse($x509Read, false);

        try {
            $fingerprint = \openssl_x509_fingerprint($x509Read, $rawData['signatureTypeSN']) ?: '';
        } catch (\Throwable $e) {
            $fingerprint = '';
        }

        $pubKeyRead = \openssl_pkey_get_public($x509Read);
        $pubKey = \openssl_pkey_get_details($pubKeyRead);

        \openssl_pkey_free($pubKeyRead);
        \openssl_x509_free($x509Read);

        if (isset($rawData['extensions']['basicConstraints']) && \mb_stripos($rawData['extensions']['basicConstraints'], 'CA:TRUE') !== false) {
            throw new ExpectedLeafCertificate();
        }

        $fields = [
            'commonName' => $rawData['subject']['commonName'],
            'altNames' => $this->getAltNames($rawData),
            'signatureAlgorithm' => $rawData['signatureTypeSN'],
            'fingerprint' => $fingerprint,
            'validTo' => (int) $rawData['validTo_time_t'],
            'validFrom' => (int) $rawData['validFrom_time_t'],
            'issuer' => $rawData['issuer'],
            'subject' => $rawData['subject'],
            'pubKey' => $pubKey['key'],
            '_privateKeyInfo' => $this->getPrivateKeyDetails($privateKey),
        ];

        $fields['_domains'] = $fields['altNames'];
        $fields['_domains'][] = $rawData['subject']['commonName'];

        // Remove any duplicates and ensure the keys are incremental.
        $fields['_domains'] = \array_unique($fields['_domains']);

        return $fields;
    }

    /**
     * @param array<string,mixed> $rawData
     *
     * @return array<int,string>
     */
    private function getAltNames(array $rawData): array
    {
        if (! isset($rawData['extensions']['subjectAltName'])) {
            return [];
        }

        return \array_map(
            static fn ($item) => \explode(':', \trim($item), 2)[1],
            \array_filter(
                \explode(',', $rawData['extensions']['subjectAltName']),
                static fn ($item) => \mb_strpos($item, ':') !== false
            )
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function getPrivateKeyDetails(HiddenString $privateKey): array
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
