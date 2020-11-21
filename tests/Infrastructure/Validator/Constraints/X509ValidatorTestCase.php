<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use ParkManager\Application\Service\TLS\CAResolver;
use ParkManager\Application\Service\TLS\CertificateValidator;
use ParkManager\Tests\Application\Service\TLS\TLSPersistenceRepositoryMock;
use Pdp\CurlHttpClient as PdpHttpClient;
use Pdp\Manager as PublicSuffixManager;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
abstract class X509ValidatorTestCase extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function getCertificateValidator(): CertificateValidator
    {
        return new CertificateValidator(
            new CAResolver(new TLSPersistenceRepositoryMock()),
            new PublicSuffixManager(new Psr16Cache(new ArrayAdapter()), new PdpHttpClient()),
            new CurlHttpClient(),
            new NullLogger()
        );
    }
}
