<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use Doctrine\Persistence\ObjectManager;
use ParkManager\Application\Service\TLS\Violation\MissingCAExtension;
use ParkManager\Application\Service\TLS\Violation\ToManyCAsProvided;
use ParkManager\Application\Service\TLS\Violation\UnableToResolveParent;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;

/**
 * @final
 */
class CAResolver
{
    private ObjectManager $objectManager;
    private X509DataExtractor $extractor;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->extractor = new X509DataExtractor();
    }

    /**
     * @param array<string,string> $caList
     */
    public function resolve(string $certificate, array $caList): ?CA
    {
        // Sanity check to prevent DoS attacks
        // Normally only two parents are used, more than three is exceptional
        if (\count($caList) > 3) {
            throw new ToManyCAsProvided();
        }

        $certData = $this->getX509Data($certificate, '');

        if ($this->isSignatureValid($certificate, $certData['_pubKey'])) {
            return null;
        }

        return $this->resolveCA($certificate, $caList);
    }

    /**
     * @return array<string,mixed>
     */
    private function getX509Data(string $contents, string $name, bool $withKey = true): array
    {
        return $this->extractor->extractRawData($contents, $name, $withKey);
    }

    private function isSignatureValid(string $contents, string $pupKey): bool
    {
        $result = openssl_x509_verify($contents, $pupKey);

        if ($result === 1) {
            return true;
        }

        @openssl_error_string();

        return false;
    }

    /**
     * @param array<string,string> $caList
     */
    private function resolveCA(string $certificate, array $caList): CA
    {
        $ca = null;

        foreach ($caList as $index => $contents) {
            /** @var CA|null $ca */
            $ca = $this->objectManager->find(CA::class, CA::getHash($contents));

            // Already exists, so we only need to check the signature and continue otherwise
            if ($ca !== null) {
                if ($this->isSignatureValid($certificate, $ca->getPublicKey())) {
                    return $ca;
                }

                continue;
            }

            $data = $this->getX509Data($contents, $index);
            $this->validateCA($data);

            if (! $this->isSignatureValid($certificate, $data['_pubKey'])) {
                continue;
            }

            $parent = null;
            $fields = [
                'subject' => $data['subject'],
                '_signatureAlgorithm' => $data['_signatureAlgorithm'],
                '_fingerprint' => $data['_fingerprint'] ?? '',
                '_validTo' => $data['_validTo'],
                '_validFrom' => $data['_validFrom'],
                'issuer' => $data['issuer'],
                '_pubKey' => $data['_pubKey'],
            ];

            // Check if self signed, otherwise resolve it's parent
            if (! $this->isSignatureValid($contents, $data['_pubKey'])) {
                // THIS issuer cannot be the parent of another parent, so remove it
                // from the list. This speeds-up the resolving process.
                unset($caList[$index]);

                $parent = $this->resolveCA($contents, $caList);
            }

            $ca = new CA($contents, $fields, $parent);
            $this->objectManager->persist($ca);

            break;
        }

        if ($ca === null) {
            $data = $this->getX509Data($certificate, '', false);

            throw new UnableToResolveParent($data['_commonName']);
        }

        return $ca;
    }

    private function validateCA(array $data): void
    {
        if (! isset($data['extensions']['basicConstraints']) || mb_stripos($data['extensions']['basicConstraints'], 'CA:TRUE') === false) {
            throw new MissingCAExtension($data['subject']['commonName']);
        }
    }
}
