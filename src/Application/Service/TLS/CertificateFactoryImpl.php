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
use ParkManager\Domain\Webhosting\SubDomain\TLS\Certificate;
use Rollerworks\Component\X509Validator\KeyValidator;
use Rollerworks\Component\X509Validator\Violation\ExpectedLeafCertificate;
use Rollerworks\Component\X509Validator\X509DataExtractor;

final class CertificateFactoryImpl implements CertificateFactory
{
    private EncryptionPublicKey $encryptionKey;
    private KeyValidator $keyValidator;
    private X509DataExtractor $extractor;

    public function __construct(
        string $encryptionKey,
        private ObjectManager $objectManager,
        private CAResolver $caResolver,
        KeyValidator $keyValidator = null,
        X509DataExtractor $dataExtractor = null
    ) {
        $this->encryptionKey = new EncryptionPublicKey(new HiddenString($encryptionKey));
        $this->keyValidator = $keyValidator ?? new KeyValidator();
        $this->extractor = $dataExtractor ?? new X509DataExtractor();
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
        $fields = $this->extractor->extractRawData($contents, '', true)->allFields;

        if (isset($fields['extensions']['basicConstraints']) && mb_stripos($fields['extensions']['basicConstraints'], 'CA:TRUE') !== false) {
            throw new ExpectedLeafCertificate();
        }

        $fields['_privateKeyInfo'] = $this->extractor->getPrivateKeyDetails($privateKey);

        unset(
            $fields['name'],
            $fields['version'],
            $fields['validFrom'],
            $fields['validTo'],
            $fields['validFrom_time_t'],
            $fields['validTo_time_t'],
            $fields['purposes'],
            $fields['extensions']
        );

        $privateKeyEncrypted = Crypto::seal($privateKey, $this->encryptionKey, Halite::ENCODE_BASE64);
        $certificate = new Certificate($contents, $privateKeyEncrypted, $fields, $ca);

        $this->objectManager->persist($certificate);

        return $certificate;
    }
}
