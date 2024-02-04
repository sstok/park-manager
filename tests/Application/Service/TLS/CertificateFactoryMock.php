<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service\TLS;

use ParagonIE\ConstantTime\Base64;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\TLS\CAResolver;
use ParkManager\Application\Service\TLS\CertificateFactory;
use ParkManager\Application\Service\TLS\CertificateFactoryImpl;
use ParkManager\Domain\Webhosting\SubDomain\TLS\Certificate;
use Rollerworks\Component\X509Validator\KeyValidator;

/**
 * Tries to Mock as much as possible of the CertificateFactory.
 *
 * Note that actual x509 decoding still happens as mocking this information
 * will require more work than is worth.
 *
 * @internal
 */
final class CertificateFactoryMock implements CertificateFactory
{
    private const PUB_KEY = 'Duh1XpZWgTTkSOaw/cZHlRIVicTM85cQznhRPTju6BM=';

    private CertificateFactoryImpl $factory;

    public function __construct(bool $mockKeyValidator = true)
    {
        $objectManager = new TLSPersistenceRepositoryMock();
        $caResolver = new CAResolver($objectManager);
        $keyValidator = $mockKeyValidator ? $this->getKeyValidatorStub() : new KeyValidator();

        $this->factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);
    }

    public function createCertificate(string $contents, HiddenString $privateKey, array $caList = []): Certificate
    {
        return $this->factory->createCertificate($contents, $privateKey, $caList);
    }

    private function getKeyValidatorStub(): KeyValidator
    {
        return new class() extends KeyValidator {
            public function validate(HiddenString | string $privateKey, string $certificate, int $minimumBitCount = self::MINIMUM_BIT_COUNT): void
            {
                // No-op
            }
        };
    }
}
