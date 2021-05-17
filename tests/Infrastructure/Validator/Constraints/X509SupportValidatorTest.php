<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use ParkManager\Domain\Exception\InvalidArgument;
use ParkManager\Infrastructure\Validator\Constraints\X509CertificateBundle;
use ParkManager\Infrastructure\Validator\Constraints\X509Support;
use ParkManager\Infrastructure\Validator\Constraints\X509SupportValidator;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * @internal
 */
final class X509SupportValidatorTest extends X509ValidatorTestCase
{
    /** @test */
    public function it_ignores_null(): void
    {
        $callback = static function ($data): void {
            throw new X509SupportStubViolation('Salsha-4234', $data['_signatureAlgorithm']);
        };

        $this->validator->validate(null, new X509Support($callback));
        $this->assertNoViolation();
    }

    /** @test */
    public function it_fails_when_callback_throws(): void
    {
        $exception = null;
        $callback = static function ($data) use (&$exception): void {
            $exception = new X509SupportStubViolation('Salsha-4234', $data['_signatureAlgorithm']);

            throw $exception;
        };

        $constraint = new X509Support($callback);

        $this->validator->validate(
            new X509CertificateBundle(
                $cert = <<<'X509'
                    -----BEGIN CERTIFICATE-----
                    MIIDKzCCAhMCCQDZHE66hI+pmjANBgkqhkiG9w0BAQUFADBUMRowGAYDVQQDDBFS
                    b2xsZXJzY2FwZXMgQ0F2MzEVMBMGA1UECgwMUm9sbGVyc2NhcGVzMRIwEAYDVQQH
                    DAlSb3R0ZXJkYW0xCzAJBgNVBAYTAk5MMB4XDTE0MDcyNzEzMDIzM1oXDTE4MDcy
                    NjEzMDIzM1owWzEhMB8GA1UEAwwYYm9wLmRldi5yb2xsZXJzY2FwZXMubmV0MRUw
                    EwYDVQQKDAxSb2xsZXJzY2FwZXMxEjAQBgNVBAcMCVJvdHRlcmRhbTELMAkGA1UE
                    BhMCTkwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDFN7758InBPIIE
                    Q/VoYrj/poR1bGEcupAB+Q68R2C5ac5EGQMwaODCphP1RetLGHJE+4hss9GzJb56
                    LfLSKy500Zk6R50zUXNCJwvkMvODHTMDy0xORg7tMbe3kLnHH/lbhmeWmXt5qDxa
                    S2jx5A2pKGmoLS8smYFlPRZ0yiK8Ugy5kDWCEFA31TIsGKcofOWcr+vfJ7HltXav
                    h1VFZ2nzJC8xKaoFQO4uake225CZQ+W4yhIxu5beY/FXlh2PIZqd1rQhQLuV5gK4
                    zGkjNkN6DVJ+7xwnYJ7yeXKlovwMOEJQG1LHnr16gFRRcFeVUHPZkW47QGOYh60n
                    rG8/8/kLAgMBAAEwDQYJKoZIhvcNAQEFBQADggEBAKLWz2F2bJyhTlHxAORbdugm
                    esBbPxlhkCitdXp7uAkQh+0HeJ+jFb+CA0blmGyY3j15t54WV9ySMV8tQRSk5sXl
                    VVaJ4AF0uIvT5gbOvL8Vr2ZNiWp2S0Nqx28JVP/KNCAI3PBIWnDcQOON3gHQQi9O
                    qmL+vAuODEQ2UvgCd2GgFPqsu79Y1PRbqRIwqNiFasHt9pQNlpzRM6AjtUMldShG
                    rpz1WIZIIZuH+TC/iqD7UlSoLxJbe79a6dbBNw7bnWlo+HDl8YfmY6Ks3O6MCbYn
                    qVBRc3K9ywcUYPJNVuUazdXuY6FSiGB1iOLxRHppQapmWK5GdtQFXW3GlkXFYf4=
                    -----END CERTIFICATE-----
                    X509
            ),
            $constraint
        );

        $this->buildViolation('tls.violation.weak_signature_algorithm')
            ->setInvalidValue($cert)
            ->setCause($exception)
            ->setParameter('{expected}', 'Salsha-4234')
            ->setParameter('{provided}', 'RSA-SHA1')
            ->assertRaised();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new X509SupportValidator(new Translator('en'), $this->getCertificateValidator());
    }
}

/**
 * @internal
 */
final class X509SupportStubViolation extends InvalidArgument
{
    private string $expected;
    private string $provided;

    public function __construct(string $expected, string $provided)
    {
        $this->expected = $expected;
        $this->provided = $provided;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.weak_signature_algorithm';
    }

    public function getTranslationArgs(): array
    {
        return [
            'expected' => $this->expected,
            'provided' => $this->provided,
        ];
    }
}
