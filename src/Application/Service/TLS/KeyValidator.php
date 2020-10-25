<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\TLS\Violation\CertificateMismatch;
use ParkManager\Application\Service\TLS\Violation\KeyBitsToLow;
use ParkManager\Application\Service\TLS\Violation\PublicKeyMismatch;
use ParkManager\Application\Service\TLS\Violation\UnprocessableKey;
use ParkManager\Application\Service\TLS\Violation\UnprocessablePEM;

/** @final */
class KeyValidator
{
    public const MINIMUM_BIT_COUNT = 2048;

    /**
     * Validates if the provided private and certificate pair match.
     *
     * Internally this check if the public-key of the private-key
     * matches with the public key of the certificate. And Then performs
     * an additional check to ensure the key was not tempered with.
     *
     * @param HiddenString $privateKey  Private-key as PEM X509 format protected-string
     * @param string       $certificate Certificate as PEM X509 format string
     *
     * @throws UnprocessablePEM    when the data cannot be parsed or processed
     * @throws PublicKeyMismatch   when the public-keys don't match
     * @throws CertificateMismatch when the private doesn't match the certificate
     * @throws KeyBitsToLow        when the private bits count is less than 2048
     */
    public function validate(HiddenString $privateKey, string $certificate): void
    {
        $certR = @\openssl_x509_read($certificate);
        $privateR = null;

        if ($certR === false) {
            throw new UnprocessablePEM('');
        }

        $pupKey = \openssl_pkey_get_public($certR);
        $key = $privateKey->getString();

        try {
            $privateR = @\openssl_pkey_get_private($key);

            if ($privateR === false) {
                throw new UnprocessableKey('Unable to read private key-data, invalid key provided?');
            }

            if (! @\openssl_x509_check_private_key($certR, $privateR)) {
                throw new PublicKeyMismatch();
            }

            // Note: technically it's rather difficult to replace the public-key
            // in a private-key (if not impossible?) yet openssl_x509_check_private_key() does
            // not provide full protection, so we use this additional check to prevent spoofing.

            // @codeCoverageIgnoreStart

            $original = "I just wanna tell you how I'm feeling\nGotta make you understand";
            $encrypted = '';

            if (! @\openssl_public_encrypt($original, $encrypted, $pupKey, OPENSSL_PKCS1_OAEP_PADDING)) {
                throw new UnprocessableKey('Unable to encrypt data, invalid key provided?');
            }

            if (! @\openssl_private_decrypt($encrypted, $decrypted, $privateR, OPENSSL_PKCS1_OAEP_PADDING) || $decrypted !== $original) {
                throw new CertificateMismatch();
            }

            // @codeCoverageIgnoreEnd

            $details = @\openssl_pkey_get_details($privateR);

            if ($details === false) {
                throw new UnprocessableKey('Unable to read private key-data.');
            }

            if ($details['bits'] < self::MINIMUM_BIT_COUNT) {
                throw new KeyBitsToLow(self::MINIMUM_BIT_COUNT, $details['bits']);
            }
        } finally {
            if (\is_resource($privateR)) {
                @\openssl_pkey_free($privateR);
                @\openssl_pkey_free($pupKey);
                @\openssl_x509_free($certR);
            }

            \sodium_memzero($key);
            unset($key);
        }
    }
}
