<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use ParkManager\Application\Service\PdpManager;
use ParkManager\Application\Service\TLS\CAResolver;
use ParkManager\Application\Service\TLS\CertificateValidator;
use ParkManager\Infrastructure\Pdp\PsrStorageFactory;
use ParkManager\Tests\Application\Service\TLS\TLSPersistenceRepositoryMock;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
abstract class X509ValidatorTestCase extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function getCertificateValidator(): CertificateValidator
    {
        $httpClient = new MockHttpClient();
        $factory = new PsrStorageFactory(new Psr16Cache(new ArrayAdapter()), $httpClient);
        $pdpManager = new PdpManager(
            $factory->createPublicSuffixListStorage(),
            $factory->createTopLevelDomainListStorage()
        );

        return new CertificateValidator(
            new CAResolver(new TLSPersistenceRepositoryMock()),
            $pdpManager,
            new CurlHttpClient(),
            new NullLogger()
        );
    }
}
