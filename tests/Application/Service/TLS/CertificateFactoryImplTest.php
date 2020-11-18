<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service\TLS;

use ParagonIE\ConstantTime\Base64;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\Halite\Halite;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\TLS\CAResolver;
use ParkManager\Application\Service\TLS\CertificateFactoryImpl;
use ParkManager\Application\Service\TLS\KeyValidator;
use ParkManager\Application\Service\TLS\Violation\ExpectedLeafCertificate;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;
use ParkManager\Domain\Webhosting\SubDomain\TLS\Certificate;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class CertificateFactoryImplTest extends TestCase
{
    use ProphecyTrait;

    private const PUB_KEY = 'Duh1XpZWgTTkSOaw/cZHlRIVicTM85cQznhRPTju6BM=';
    private const PRIVATE_KEY = 'T+Jk39QnDS7vet7xiiW8dURSyTwakUI6XBQwJV5XJkA=';

    /** @test */
    public function it_creates_cert_not_previously_stored(): void
    {
        $ca = new CA('MIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV', [
            'pubKey' => '-----BEGIN PUBLIC KEY-----MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA9G4MAqOU6tgIw2gJtZVu-----END PUBLIC KEY-----',
            'commonName' => 'Rollerscapes CAv3',
            'signatureAlgorithm' => 'RSA-SHA1',
            'issuer' => [
                'commonName' => 'Rollerscapes CAv3',
                'organizationName' => 'Rollerscapes',
                'localityName' => 'Rotterdam',
                'countryName' => 'NL',
            ],
            'subject' => [
                'commonName' => 'Rollerscapes CAv3',
                'organizationName' => 'Rollerscapes',
                'localityName' => 'Rotterdam',
                'countryName' => 'NL',
            ],
            'fingerprint' => '',
            'validTo' => 1522334199,
            'validFrom' => 1396190199,
        ]);

        $certContents = <<<'CERT'
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
            CERT;

        $objectManager = new TLSPersistenceRepositoryMock();

        $caResolverProphecy = $this->prophesize(CAResolver::class);
        $caResolverProphecy->resolve($certContents, ['ca' => 'MIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV'])->willReturn($ca);
        $caResolver = $caResolverProphecy->reveal();

        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver);

        $certificate = $factory->createCertificate(
            $certContents,
            new HiddenString($privateKey = <<<'PRIV_KEY'
                -----BEGIN RSA PRIVATE KEY-----
                MIIEogIBAAKCAQEAxTe++fCJwTyCBEP1aGK4/6aEdWxhHLqQAfkOvEdguWnORBkD
                MGjgwqYT9UXrSxhyRPuIbLPRsyW+ei3y0isudNGZOkedM1FzQicL5DLzgx0zA8tM
                TkYO7TG3t5C5xx/5W4Znlpl7eag8Wkto8eQNqShpqC0vLJmBZT0WdMoivFIMuZA1
                ghBQN9UyLBinKHzlnK/r3yex5bV2r4dVRWdp8yQvMSmqBUDuLmpHttuQmUPluMoS
                MbuW3mPxV5YdjyGanda0IUC7leYCuMxpIzZDeg1Sfu8cJ2Ce8nlypaL8DDhCUBtS
                x569eoBUUXBXlVBz2ZFuO0BjmIetJ6xvP/P5CwIDAQABAoIBAEZcy0A1N5C/28tV
                y7rAbiyX5m5WipdLYJGzoDRAaxv7yeG14tNkt7v6sOgzV+1k/W/rJhNSXKDD+J9y
                wU2Gpn57QWXvowBqMOsLL0zteL/wrQDPiZvrluu9b0SI2B9ZIwgqfc7XV5xiD5ZP
                jVOv/8e4aWndJRWOdwH9t4NXkukI5Joc/l0JvLVlteBwJO22JvWp3skBiNBCwP/e
                +tx9570QJederODEkf0wPpD4PSMM86GpP5x0+NGfO+fn0AD2adSmOSRnzO769AzH
                l3R5Oh2tMFgnyxmLYpa/DL1XAgR6vIPkgJOVkcbg19yps+f35Mi1n9e63QDEB8lI
                fkRFtAECgYEA6Wxvd9miW5ts02K34oxm/UWp6trZKthhWQ0J2JDn7dvO6KnyIzpw
                cfEv6wRHxtSot/VkV1Qf6YwPKvl8KkYVDXbs9AZ4nzEXp6GSkf2SEGx2h2Gofiwq
                DkWRnaI/1kM4ukzW16PiumTd8KQis6V7/2y9Kw1t9u2DyYUv6KfIUAsCgYEA2Era
                4jQ4VQMJBBY8pQN+gMpH+avytvGGHXl/tm6My7LevEZOq00LAhlsa/fwUxI1dXhH
                yFXtQIILZw79a1bRWsbfFrkWiC9g0JgNDt/pzds2EsTltVS5OWRMaVcrL3glP8+U
                ObW4qzTJiI6m6LKV7hnmaL1fR/NUjWk+fvc/mwECgYAMs3fFP7RT47siLWbwDs+z
                zEyYmNvkNu3lGI6GmCvmh2VUx5qDTDS+Hm+LDCqTqRKdH98b2Vn7LUHOBtE4w6N1
                nhj6ljeOAe/VkTcWdoOyHRS9/RRb+S84o5RuzVtH31SA3pl6FlLJ7Z8d7vBscf6z
                QUlxxENNglL/bh3TPP3rTQKBgC8LwSZ4s1QSb/CaoaBG7Uo4NYWiGA4g5MoedmAJ
                Fcjs5DPRmyT5gg531zR43qZDDKu7eOmjfxKL9sz43rhtTuZO4ZGAutzuaUGWASke
                HS3wo4dbmpdhkVRhc5lqI3OUz41cqmIPG9bpiXiRhs6QoboDmjFoF4R/8gE8RiK5
                xvUBAoGACrghAg+GlJZ/Aogx7wK6b1k8rfcpgIoHxOPiqIgyMgevTT6D6w8D0CqI
                cEVTZ/fm+EaNuMZvxqSG5f19/obLus+VNXvnMYi3qwFAZ5NhKBen12YhIcaZpOh1
                ZSjeYozDCyRmv76q3sqcLrwxnULIcaK0l255ZczzwiUl39Bqe1o=
                -----END RSA PRIVATE KEY-----
                PRIV_KEY
            ),
            [
                'ca' => <<<'CERT'
                    MIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV
                    CERT,
            ]
        );

        // - and try multiple certificate types (RSA-SHA1, RSA-SHA256, etc). Or one with a fingerprint...
        self::assertEquals(['bop.dev.rollerscapes.net'], $certificate->getDomains());
        self::assertEquals('bop.dev.rollerscapes.net', $certificate->getCommonName());
        self::assertEquals([], $certificate->getAdditionalDomains());
        self::assertEquals('RSA-SHA1', $certificate->getSignatureAlgorithm());
        self::assertEquals('5990c7371c6e72708f0df1444c057a14c193131d', $certificate->getFingerprint());
        self::assertEquals('2014-07-27 13:02:33', $certificate->validFromDate()->toDateTimeString());
        self::assertEquals('2018-07-26 13:02:33', $certificate->expirationDate()->toDateTimeString());
        self::assertEquals(<<<'PUBKEY'
            -----BEGIN PUBLIC KEY-----
            MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxTe++fCJwTyCBEP1aGK4
            /6aEdWxhHLqQAfkOvEdguWnORBkDMGjgwqYT9UXrSxhyRPuIbLPRsyW+ei3y0isu
            dNGZOkedM1FzQicL5DLzgx0zA8tMTkYO7TG3t5C5xx/5W4Znlpl7eag8Wkto8eQN
            qShpqC0vLJmBZT0WdMoivFIMuZA1ghBQN9UyLBinKHzlnK/r3yex5bV2r4dVRWdp
            8yQvMSmqBUDuLmpHttuQmUPluMoSMbuW3mPxV5YdjyGanda0IUC7leYCuMxpIzZD
            eg1Sfu8cJ2Ce8nlypaL8DDhCUBtSx569eoBUUXBXlVBz2ZFuO0BjmIetJ6xvP/P5
            CwIDAQAB
            -----END PUBLIC KEY-----

            PUBKEY,
            $certificate->getPublicKey()
        );
        self::assertEquals(
            [
                'commonName' => 'bop.dev.rollerscapes.net',
                'altNames' => [],
                'signatureAlgorithm' => 'RSA-SHA1',
                'fingerprint' => '5990c7371c6e72708f0df1444c057a14c193131d',
                'validTo' => 1532610153,
                'validFrom' => 1406466153,
                'issuer' => [
                    'commonName' => 'Rollerscapes CAv3',
                    'organizationName' => 'Rollerscapes',
                    'localityName' => 'Rotterdam',
                    'countryName' => 'NL',
                ],
                'subject' => [
                    'commonName' => 'bop.dev.rollerscapes.net',
                    'organizationName' => 'Rollerscapes',
                    'localityName' => 'Rotterdam',
                    'countryName' => 'NL',
                ],
                '_privateKeyInfo' => [
                    'bits' => 2048,
                    'type' => 0,
                ],
                '_domains' => ['bop.dev.rollerscapes.net'],
            ],
            $certificate->getRawFields()
        );

        self::assertEquals($ca, $certificate->ca);
        self::assertSame(
            $privateKey,
            Crypto::unseal($certificate->getPrivateKey(), new EncryptionSecretKey(new HiddenString(Base64::decode(self::PRIVATE_KEY))), Halite::ENCODE_BASE64)
                ->getString()
        );
        $objectManager->assertEntitiesCountWasSaved(1);
        $objectManager->assertEntitiesWereSavedThat(static fn (Certificate $certificate) => $certificate->getContents() === $certContents);
    }

    /** @test */
    public function it_creates_with_alt_names(): void
    {
        // Note. Not the actual CA, but it's for example only.
        $ca = new CA('MIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV', [
            'pubKey' => '-----BEGIN PUBLIC KEY-----MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA9G4MAqOU6tgIw2gJtZVu-----END PUBLIC KEY-----',
            'commonName' => 'Rollerscapes CAv3',
            'signatureAlgorithm' => 'RSA-SHA1',
            'issuer' => [
                'commonName' => 'Rollerscapes CAv3',
                'organizationName' => 'Rollerscapes',
                'localityName' => 'Rotterdam',
                'countryName' => 'NL',
            ],
            'subject' => [
                'commonName' => 'Rollerscapes CAv3',
                'organizationName' => 'Rollerscapes',
                'localityName' => 'Rotterdam',
                'countryName' => 'NL',
            ],
            'fingerprint' => '',
            'validTo' => 1522334199,
            'validFrom' => 1396190199,
        ]);

        $privateKey = new HiddenString(
            <<<'PRIV_KEY'
                -----BEGIN RSA PRIVATE KEY-----
                MIIEogIBAAKCAQEAxTe++fCJwTyCBEP1aGK4/6aEdWxhHLqQAfkOvEdguWnORBkD
                MGjgwqYT9UXrSxhyRPuIbLPRsyW+ei3y0isudNGZOkedM1FzQicL5DLzgx0zA8tM
                TkYO7TG3t5C5xx/5W4Znlpl7eag8Wkto8eQNqShpqC0vLJmBZT0WdMoivFIMuZA1
                ghBQN9UyLBinKHzlnK/r3yex5bV2r4dVRWdp8yQvMSmqBUDuLmpHttuQmUPluMoS
                MbuW3mPxV5YdjyGanda0IUC7leYCuMxpIzZDeg1Sfu8cJ2Ce8nlypaL8DDhCUBtS
                x569eoBUUXBXlVBz2ZFuO0BjmIetJ6xvP/P5CwIDAQABAoIBAEZcy0A1N5C/28tV
                y7rAbiyX5m5WipdLYJGzoDRAaxv7yeG14tNkt7v6sOgzV+1k/W/rJhNSXKDD+J9y
                wU2Gpn57QWXvowBqMOsLL0zteL/wrQDPiZvrluu9b0SI2B9ZIwgqfc7XV5xiD5ZP
                jVOv/8e4aWndJRWOdwH9t4NXkukI5Joc/l0JvLVlteBwJO22JvWp3skBiNBCwP/e
                +tx9570QJederODEkf0wPpD4PSMM86GpP5x0+NGfO+fn0AD2adSmOSRnzO769AzH
                l3R5Oh2tMFgnyxmLYpa/DL1XAgR6vIPkgJOVkcbg19yps+f35Mi1n9e63QDEB8lI
                fkRFtAECgYEA6Wxvd9miW5ts02K34oxm/UWp6trZKthhWQ0J2JDn7dvO6KnyIzpw
                cfEv6wRHxtSot/VkV1Qf6YwPKvl8KkYVDXbs9AZ4nzEXp6GSkf2SEGx2h2Gofiwq
                DkWRnaI/1kM4ukzW16PiumTd8KQis6V7/2y9Kw1t9u2DyYUv6KfIUAsCgYEA2Era
                4jQ4VQMJBBY8pQN+gMpH+avytvGGHXl/tm6My7LevEZOq00LAhlsa/fwUxI1dXhH
                yFXtQIILZw79a1bRWsbfFrkWiC9g0JgNDt/pzds2EsTltVS5OWRMaVcrL3glP8+U
                ObW4qzTJiI6m6LKV7hnmaL1fR/NUjWk+fvc/mwECgYAMs3fFP7RT47siLWbwDs+z
                zEyYmNvkNu3lGI6GmCvmh2VUx5qDTDS+Hm+LDCqTqRKdH98b2Vn7LUHOBtE4w6N1
                nhj6ljeOAe/VkTcWdoOyHRS9/RRb+S84o5RuzVtH31SA3pl6FlLJ7Z8d7vBscf6z
                QUlxxENNglL/bh3TPP3rTQKBgC8LwSZ4s1QSb/CaoaBG7Uo4NYWiGA4g5MoedmAJ
                Fcjs5DPRmyT5gg531zR43qZDDKu7eOmjfxKL9sz43rhtTuZO4ZGAutzuaUGWASke
                HS3wo4dbmpdhkVRhc5lqI3OUz41cqmIPG9bpiXiRhs6QoboDmjFoF4R/8gE8RiK5
                xvUBAoGACrghAg+GlJZ/Aogx7wK6b1k8rfcpgIoHxOPiqIgyMgevTT6D6w8D0CqI
                cEVTZ/fm+EaNuMZvxqSG5f19/obLus+VNXvnMYi3qwFAZ5NhKBen12YhIcaZpOh1
                ZSjeYozDCyRmv76q3sqcLrwxnULIcaK0l255ZczzwiUl39Bqe1o=
                -----END RSA PRIVATE KEY-----
                PRIV_KEY
        );
        $certContents = <<<'CERT'
            -----BEGIN CERTIFICATE-----
            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
            -----END CERTIFICATE-----
            CERT;

        $objectManager = new TLSPersistenceRepositoryMock();

        $caResolverProphecy = $this->prophesize(CAResolver::class);
        $caResolverProphecy->resolve($certContents, [])->willReturn($ca);
        $caResolver = $caResolverProphecy->reveal();

        $keyValidatorProphecy = $this->prophesize(KeyValidator::class);
        $keyValidatorProphecy->validate($privateKey, $certContents)->shouldBeCalledOnce();
        $keyValidator = $keyValidatorProphecy->reveal();

        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);

        $certificate = $factory->createCertificate($certContents, $privateKey);

        self::assertEquals('slack.com', $certificate->getDomain());
        self::assertEquals('slack.com', $certificate->getCommonName());
        self::assertEquals(['slack.com', '*.slack.com'], $certificate->getDomains());
        self::assertEquals(['slack.com', '*.slack.com'], $certificate->getAdditionalDomains());
        self::assertEquals('RSA-SHA256', $certificate->getSignatureAlgorithm());
        self::assertEquals('5f187452a024f2af605e8c01f2a5e22a7a530870a36ba459ca8b56048a454187', $certificate->getFingerprint());
        self::assertEquals('2018-02-08 00:00:00', $certificate->validFromDate()->toDateTimeString());
        self::assertEquals('2021-02-12 12:00:00', $certificate->expirationDate()->toDateTimeString());
        self::assertEquals(<<<'PUBKEY'
            -----BEGIN PUBLIC KEY-----
            MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqb0QCgBUkwHwC1AUT1N1
            W6wfbKSUZGSQ9Pf7EovdVIt1f8hrq5KZOvVUaU/5qsS9UMm1GGqhjVrFqRKv//rZ
            /VaIThNaLVGQ3yTWCmnPxTZBvEOH1oLPi2V+XgDcX2drRUUfFELQy8EZVABwQu5Y
            3FluB1S7Nv1EH2tOsug5koMIdtMetUo/nKPzpuVC/4C/8oPN3+37cSriAImfxrif
            rrSCLkMscnwh6VcSuajnlCgw/iVcQzEE0OGht+KmFgIvjTWmKLx44MvkKqPUnvBu
            dKk4k+9V527g9uNM0rxCVXWb1hf5w08IVvEC5/N78HrBl/q/e2oaygp95z/CQ5aJ
            qQIDAQAB
            -----END PUBLIC KEY-----

            PUBKEY,
            $certificate->getPublicKey()
        );
        self::assertEquals($ca, $certificate->ca);
        $objectManager->assertEntitiesCountWasSaved(1);
        $objectManager->assertEntitiesWereSavedThat(static fn (Certificate $certificate) => $certificate->getContents() === $certContents);
    }

    /** @test */
    public function it_creates_with_ip_address(): void
    {
        $privateKey = new HiddenString(
            <<<'PRIV_KEY'
                -----BEGIN PRIVATE KEY-----
                MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDFjP97gx83vtMQ
                wPSuvgvnDtQu4U6Tis7eQgD/f6p1QrHBVXkGloj2JDSkT5fsWUlEeidkuPRljsyF
                1Ot1536/3ZbyrKOlc0uL1Dfn7tflv/F/U3s7FvGhFgsXKWtrRFot89T1dPezkqLH
                uyRLUF+wpjT1LIAbS6/w+87LvtVjid8IlhQAFc0kDrs+n1pCHCF95z7LMTmJCss7
                UiGeioG09ERBY3hSl5Xx0f+EH+3W6ZBO1mjoAqfLyoLc0/6shv5IcHS+6PCwpzQa
                V99C8IMN/bOB1I1WXX1PyZ2K1uHrPwS3cs7aWUe3r3UZm1BpCTBuFGiW88JMH/CN
                HUEyRuNbAgMBAAECggEAfVCs/Ee4NN+K+lS8omCSIldox/hKpRNnmNvb0DfHBK1z
                HwY+SKyTYziiYBzRba9R5+tpM96TwhysprTjTJ6167YAPB7BjIGvyZqsgCcls2to
                IXHueRRb4OifzmiK6LeqUP4c+DvjqXj6Y6LWKiRpHyC/9UruVOJVUJQboWmD6ah8
                0ymH6UA36EBNQeUi6FGkbG5Lq3obJ0URnz6ZqAS/hQ/7yLhHHQZJfDuk6nuodanp
                KSZqPDYnZks5ShW2nipySZGZJf2komc7DrkILpUqML54RWlf66IcHbjdMiecWM+J
                Vk7vVI1GPnk1KalSsz3OEl2s0Z83POLlGz3s4YXhsQKBgQDxpQs9Ug7BogI+1jWQ
                /LYl8Z5CDTZ9LeGxX3+IgHB//O5lbGP+BfCtPKm8ePvbCsUndUeU0Zk44KXPWIRG
                2uD1JJcRM+xLLlt2zepWPps71+9zNkrVknUNKLf3bRaRVIoUoGQvn/heHWD8GWQL
                q4E1XD9V5Ica8Ncwj+XXXlFMuQKBgQDRSV6Ldoz7tD8IVmSYq6usKR71mbR4jL38
                aoXr2CobLTD2xon5jKROqOu8S/xRmWr0dDPTU4aqvT0s0XtBfZZkWZO6Fngv/g6Y
                a2UmGYa6H4twpBosMQl9INMz5O8a66YXI02rD3eMzKmfj+M/qa5sFvN3jfBiF7BQ
                BsE4EE0uswKBgQCqbWsF7q/1pVvMEaxp+7JEBKtHjqYVl5yMSvxZu6ycuDkzU0Ua
                zfm3VQepo/KNxITUlYTM0x8r3FVVbeqtRoZOn2XkfihzxdhAbG6QMUuu41dAfNQ+
                2TKw+zz0xv2ZtnREo/LFHCBB0JqscwP6rxVMGymYXLtlJuO+nl7OjPjuEQKBgEcn
                A460ol/cuuVRweepzba9UDo9pH6rktfjO16gYkhVB+WOQFBoQWBAy8x7pO/1EcjE
                TO5W4lzf85FuMtJkKSI61h+ZDgl6/WlScdQPL5/No7GLCR7nGQvCgiuNdMEZRqFt
                LjWvD3z+A9ksqRz2ykcTUnjd99DRru62LTCrUiJDAoGANopyHhsiYKDAN0esLQpW
                Lc5iq+gzSBQw23xQoCxpEzQIeempoG0emT2ns7BsabOw0bu+s3HpKrv0NXcZ9eBK
                dqLe2xNta45eyOTld7rpaHuBdmUfC2RAJnAGLFR664klS0ZPFjJPSzcM3bFF6Zc1
                DUMcTMd1bTx2p5HJkVoBPIA=
                -----END PRIVATE KEY-----
                PRIV_KEY
        );
        $certContents = <<<'CERT'
            -----BEGIN CERTIFICATE-----
            MIIDjDCCAnSgAwIBAgIJAMAjrYPM6uVEMA0GCSqGSIb3DQEBCwUAMGkxCzAJBgNV
            BAYTAlhYMQwwCgYDVQQIDANOL0ExDDAKBgNVBAcMA04vQTEjMCEGA1UECgwaU2Vs
            Zi1zaWduZWQgSVAgY2VydGlmaWNhdGUxGTAXBgNVBAMMEHBhcmstbWFuYWdlci5j
            b20wHhcNMjAxMTAyMTUwMjQ4WhcNMjIxMTAyMTUwMjQ4WjBpMQswCQYDVQQGEwJY
            WDEMMAoGA1UECAwDTi9BMQwwCgYDVQQHDANOL0ExIzAhBgNVBAoMGlNlbGYtc2ln
            bmVkIElQIGNlcnRpZmljYXRlMRkwFwYDVQQDDBBwYXJrLW1hbmFnZXIuY29tMIIB
            IjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxYz/e4MfN77TEMD0rr4L5w7U
            LuFOk4rO3kIA/3+qdUKxwVV5BpaI9iQ0pE+X7FlJRHonZLj0ZY7MhdTrded+v92W
            8qyjpXNLi9Q35+7X5b/xf1N7OxbxoRYLFylra0RaLfPU9XT3s5Kix7skS1BfsKY0
            9SyAG0uv8PvOy77VY4nfCJYUABXNJA67Pp9aQhwhfec+yzE5iQrLO1IhnoqBtPRE
            QWN4UpeV8dH/hB/t1umQTtZo6AKny8qC3NP+rIb+SHB0vujwsKc0GlffQvCDDf2z
            gdSNVl19T8mditbh6z8Et3LO2llHt691GZtQaQkwbhRolvPCTB/wjR1BMkbjWwID
            AQABozcwNTAzBgNVHREELDAqhxAAAAAAAP//////////////hwR/AAABhxAGZgAA
            AHUAYwBrACAAdABoMA0GCSqGSIb3DQEBCwUAA4IBAQANxHGc55DhcLNNse8yhxF0
            5dH7QLjrBrk/ZteB2cPrc1VeaWflL8IKuRj6fqgqWWoeC35ufhU/ft6BpwVNCxCK
            F91VuSeiVpoZeRXPDnKYmgGr+oi7WnrPhY02ABFEt2z7sloSZFge77oyouCydnHE
            wiTIfcf8V2E+ukC4D2Xfjq2U305mIMCnRQvTkQ6uI4ZmRuIl8FNvW/TDLC01QUxK
            GtDIaQrQaj1ACg9vJUovYHNg1mYc6xnFv9NaKwhpR14LrG/fduM0AG5dsxLNoP4Q
            mThdLEWe6CvSDHT1a/bZhUIHx4GOXeIeECh+NIyhxMZQbHWJSg7IyLc3kvSd6tl9
            -----END CERTIFICATE-----
            CERT;

        $objectManager = new TLSPersistenceRepositoryMock();

        $caResolverProphecy = $this->prophesize(CAResolver::class);
        $caResolverProphecy->resolve($certContents, [])->willReturn(null);
        $caResolver = $caResolverProphecy->reveal();

        $keyValidatorProphecy = $this->prophesize(KeyValidator::class);
        $keyValidatorProphecy->validate($privateKey, $certContents)->shouldBeCalledOnce();
        $keyValidator = $keyValidatorProphecy->reveal();

        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);

        $certificate = $factory->createCertificate($certContents, $privateKey);

        self::assertEquals('park-manager.com', $certificate->getDomain());
        self::assertEquals('park-manager.com', $certificate->getCommonName());
        self::assertEquals(['0:0:FF:FFFF:FFFF:FFFF:FFFF:FFFF', '127.0.0.1', '666:0:75:63:6B:20:74:68', 'park-manager.com'], $certificate->getDomains());
        self::assertEquals(['0:0:FF:FFFF:FFFF:FFFF:FFFF:FFFF', '127.0.0.1', '666:0:75:63:6B:20:74:68'], $certificate->getAdditionalDomains());
        self::assertEquals('RSA-SHA256', $certificate->getSignatureAlgorithm());
        self::assertNull($certificate->ca);
    }

    /** @test */
    public function it_creates_cert_previously_stored(): void
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\nMIIEogIBAAKCAQEAxTe++fCJwTyCBEP1aGK4/6aEdWxhHLqQAfkOvEdguWnORBkD\n-----END RSA PRIVATE KEY-----";
        $certContents = "-----BEGIN CERTIFICATE-----\nMIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV";

        $storedCertificate = new Certificate($certContents, $privateKey, [
            'commonName' => 'example.com',
            '_domains' => ['example.com'],
            'pubKey' => 'Here\'s the key Robby!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            'issuer' => ['commonName' => 'example.com'],
            'subject' => ['commonName' => 'example.com'],
        ]);

        $keyValidatorProphecy = $this->prophesize(KeyValidator::class);
        $keyValidatorProphecy->validate(new HiddenString($privateKey), $certContents)->shouldBeCalledOnce();
        $keyValidator = $keyValidatorProphecy->reveal();

        $caResolverProphecy = $this->prophesize(CAResolver::class);
        $caResolverProphecy->resolve(Argument::any(), Argument::any())->shouldNotBeCalled();
        $caResolver = $caResolverProphecy->reveal();

        $objectManager = new TLSPersistenceRepositoryMock([$storedCertificate]);
        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);

        $certificate = $factory->createCertificate($certContents, new HiddenString($privateKey), []);

        self::assertSame($storedCertificate, $certificate);

        $objectManager->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_fails_with_ca_provided_as_cert(): void
    {
        $objectManager = new TLSPersistenceRepositoryMock();

        $keyValidatorProphecy = $this->prophesize(KeyValidator::class);
        $keyValidatorProphecy->validate(new HiddenString('-----BEGIN RSA PRIVATE KEY-----'), Argument::any())->shouldBeCalledOnce();
        $keyValidator = $keyValidatorProphecy->reveal();

        $caResolverProphecy = $this->prophesize(CAResolver::class);
        $caResolverProphecy->resolve(Argument::any(), [])->willReturn(null);
        $caResolver = $caResolverProphecy->reveal();

        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);

        $this->expectException(ExpectedLeafCertificate::class);

        $factory->createCertificate(
            <<<'CERT'
                -----BEGIN CERTIFICATE-----
                MIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV
                BAMMEVJvbGxlcnNjYXBlcyBDQXYzMRUwEwYDVQQKDAxSb2xsZXJzY2FwZXMxEjAQ
                BgNVBAcMCVJvdHRlcmRhbTELMAkGA1UEBhMCTkwwHhcNMTQwMzMwMTQzNjM5WhcN
                MTgwMzI5MTQzNjM5WjBUMRowGAYDVQQDDBFSb2xsZXJzY2FwZXMgQ0F2MzEVMBMG
                A1UECgwMUm9sbGVyc2NhcGVzMRIwEAYDVQQHDAlSb3R0ZXJkYW0xCzAJBgNVBAYT
                Ak5MMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA9G4MAqOU6tgIw2gJ
                tZVul3Ef6W37fK2p8MooXJmFRNs6QGloy8bkbAG6rLrmPlOpG4LT6jDpiUOgk4IL
                v0HZr8tSaJCEUaYWYQkc58HqZK0FfVrrzAQC8lVcO2Xl0HehEoPAtVrX+1h2F6/E
                38xzmlbUo2Ileiy6ur0KjCo+p22fd+NIEwvtbd1uySA5GsyzIq0vqpRHJzihgXXU
                TIjxdxZqqHjGslT9Ei97XEYErjFrxlwk8lNFUvxE3u2Xhfhy6qNT1CpcPg8pRVHw
                IdYqn0ApJPxLchfGjuVmcgmnDeTmBtbNGBPw1dsmswm/nvZC8CiDuqgn6PVIhpio
                Eru22wIDAQABo1AwTjAdBgNVHQ4EFgQUAe/6RHDxw475z5c8niR0o4ZiYn0wHwYD
                VR0jBBgwFoAUAe/6RHDxw475z5c8niR0o4ZiYn0wDAYDVR0TBAUwAwEB/zANBgkq
                hkiG9w0BAQUFAAOCAQEA4VMyvK2I2naw+0rm4wu9rRWOoCYuRRchkE+CvFoUDnQq
                CvWKaQApPA2qud4gA+S743GduzSf4jfAe8yGY3oA+bUAnqupF+8l19b6GcMfEop7
                LRkeiSxAVrK2hHxGYMdLXBFqBMS5PaG2LT/m1zjk+j5CJVKAtWHlO8sERSyCqa04
                2wvjlA/ArnZkt8A56kOFeIK2UBOzTozYmW+D5ZkB41JtzquO7Rty/YhVpuOfCoLX
                HuwXgPLW3fDUFmEFnIMqDCxZA5NEc+1QapjBkC8cU4xPKjIE3Ljm4Nhq0I67ipC1
                Jzgsmb7yKoigkH/BZ5sm/spdlz3/eXuEtcC6gLfsPA==
                -----END CERTIFICATE-----
                CERT,
            new HiddenString('-----BEGIN RSA PRIVATE KEY-----')
        );
    }

    /** @test */
    public function it_fails_when_private_key_is_invalid(): void
    {
        $certContents = <<<'CERT'
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
            CERT;

        $objectManager = new TLSPersistenceRepositoryMock();

        $caResolverProphecy = $this->prophesize(CAResolver::class);
        $caResolverProphecy->resolve($certContents, [])->willReturn(null);
        $caResolver = $caResolverProphecy->reveal();

        $keyValidatorProphecy = $this->prophesize(KeyValidator::class);
        $keyValidatorProphecy->validate(Argument::any(), Argument::any())->shouldBeCalledOnce();
        $keyValidator = $keyValidatorProphecy->reveal();

        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);

        $this->expectExceptionObject(new \RuntimeException('Unable to read private key-data, invalid key provided?'));

        $factory->createCertificate(
            $certContents,
            new HiddenString($privateKey = <<<'PRIV_KEY'
                -----BEGIN RSA PRIVATE KEY -----
                MIIEogIBAAKCAQEAxTe++fCJwTyCBEP1aGK4/6aEdWxhHLqQAfkOvEdguWnORBkD
                MGjgwqYT9UXrSxhyRPuIbLPRsyW+ei3y0isudNGZOkedM1FzQicL5DLzgx0zA8tM
                TkYO7TG3t5C5xx/5W4Znlpl7eag8Wkto8eQNqShpqC0vLJmBZT0WdMoivFIMuZA1
                ghBQN9UyLBinKHzlnK/r3yex5bV2r4dVRWdp8yQvMSmqBUDuLmpHttuQmUPluMoS
                MbuW3mPxV5YdjyGanda0IUC7leYCuMxpIzZDeg1Sfu8cJ2Ce8nlypaL8DDhCUBtS
                x569eoBUUXBXlVBz2ZFuO0BjmIetJ6xvP/P5CwIDAQABAoIBAEZcy0A1N5C/28tV
                y7rAbiyX5m5WipdLYJGzoDRAaxv7yeG14tNkt7v6sOgzV+1k/W/rJhNSXKDD+J9y
                wU2Gpn57QWXvowBqMOsLL0zteL/wrQDPiZvrluu9b0SI2B9ZIwgqfc7XV5xiD5ZP
                jVOv/8e4aWndJRWOdwH9t4NXkukI5Joc/l0JvLVlteBwJO22JvWp3skBiNBCwP/e
                +tx9570QJederODEkf0wPpD4PSMM86GpP5x0+NGfO+fn0AD2adSmOSRnzO769AzH
                l3R5Oh2tMFgnyxmLYpa/DL1XAgR6vIPkgJOVkcbg19yps+f35Mi1n9e63QDEB8lI
                fkRFtAECgYEA6Wxvd9miW5ts02K34oxm/UWp6trZKthhWQ0J2JDn7dvO6KnyIzpw
                cfEv6wRHxtSot/VkV1Qf6YwPKvl8KkYVDXbs9AZ4nzEXp6GSkf2SEGx2h2Gofiwq
                DkWRnaI/1kM4ukzW16PiumTd8KQis6V7/2y9Kw1t9u2DyYUv6KfIUAsCgYEA2Era
                4jQ4VQMJBBY8pQN+gMpH+avytvGGHXl/tm6My7LevEZOq00LAhlsa/fwUxI1dXhH
                yFXtQIILZw79a1bRWsbfFrkWiC9g0JgNDt/pzds2EsTltVS5OWRMaVcrL3glP8+U
                ObW4qzTJiI6m6LKV7hnmaL1fR/NUjWk+fvc/mwECgYAMs3fFP7RT47siLWbwDs+z
                zEyYmNvkNu3lGI6GmCvmh2VUx5qDTDS+Hm+LDCqTqRKdH98b2Vn7LUHOBtE4w6N1
                nhj6ljeOAe/VkTcWdoOyHRS9/RRb+S84o5RuzVtH31SA3pl6FlLJ7Z8d7vBscf6z
                QUlxxENNglL/bh3TPP3rTQKBgC8LwSZ4s1QSb/CaoaBG7Uo4NYWiGA4g5MoedmAJ
                Fcjs5DPRmyT5gg531zR43qZDDKu7eOmjfxKL9sz43rhtTuZO4ZGAutzuaUGWASke
                HS3wo4dbmpdhkVRhc5lqI3OUz41cqmIPG9bpiXiRhs6QoboDmjFoF4R/8gE8RiK5
                xvUBAoGACrghAg+GlJZ/Aogx7wK6b1k8rfcpgIoHxOPiqIgyMgevTT6D6w8D0CqI
                cEVTZ/fm+EaNuMZvxqSG5f19/obLus+VNXvnMYi3qwFAZ5NhKBen12YhIcaZpOh1
                ZSjeYozDCyRmv76q3sqcLrwxnULIcaK0l255ZczzwiUl39Bqe1o=
                -----END RSA PRIVATE KEY-----
                PRIV_KEY
            )
        );
    }

    /**
     * @test
     */
    public function complete_operation(): void
    {
        $objectManager = new TLSPersistenceRepositoryMock();

        $keyValidator = new KeyValidator();
        $caResolver = new CAResolver($objectManager);
        $factory = new CertificateFactoryImpl(Base64::decode(self::PUB_KEY), $objectManager, $caResolver, $keyValidator);

        $factory->createCertificate(
            $cert = <<<'CERT'
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
                CERT,
            new HiddenString($privateKey = <<<'PRIV_KEY'
                -----BEGIN RSA PRIVATE KEY-----
                MIIEogIBAAKCAQEAxTe++fCJwTyCBEP1aGK4/6aEdWxhHLqQAfkOvEdguWnORBkD
                MGjgwqYT9UXrSxhyRPuIbLPRsyW+ei3y0isudNGZOkedM1FzQicL5DLzgx0zA8tM
                TkYO7TG3t5C5xx/5W4Znlpl7eag8Wkto8eQNqShpqC0vLJmBZT0WdMoivFIMuZA1
                ghBQN9UyLBinKHzlnK/r3yex5bV2r4dVRWdp8yQvMSmqBUDuLmpHttuQmUPluMoS
                MbuW3mPxV5YdjyGanda0IUC7leYCuMxpIzZDeg1Sfu8cJ2Ce8nlypaL8DDhCUBtS
                x569eoBUUXBXlVBz2ZFuO0BjmIetJ6xvP/P5CwIDAQABAoIBAEZcy0A1N5C/28tV
                y7rAbiyX5m5WipdLYJGzoDRAaxv7yeG14tNkt7v6sOgzV+1k/W/rJhNSXKDD+J9y
                wU2Gpn57QWXvowBqMOsLL0zteL/wrQDPiZvrluu9b0SI2B9ZIwgqfc7XV5xiD5ZP
                jVOv/8e4aWndJRWOdwH9t4NXkukI5Joc/l0JvLVlteBwJO22JvWp3skBiNBCwP/e
                +tx9570QJederODEkf0wPpD4PSMM86GpP5x0+NGfO+fn0AD2adSmOSRnzO769AzH
                l3R5Oh2tMFgnyxmLYpa/DL1XAgR6vIPkgJOVkcbg19yps+f35Mi1n9e63QDEB8lI
                fkRFtAECgYEA6Wxvd9miW5ts02K34oxm/UWp6trZKthhWQ0J2JDn7dvO6KnyIzpw
                cfEv6wRHxtSot/VkV1Qf6YwPKvl8KkYVDXbs9AZ4nzEXp6GSkf2SEGx2h2Gofiwq
                DkWRnaI/1kM4ukzW16PiumTd8KQis6V7/2y9Kw1t9u2DyYUv6KfIUAsCgYEA2Era
                4jQ4VQMJBBY8pQN+gMpH+avytvGGHXl/tm6My7LevEZOq00LAhlsa/fwUxI1dXhH
                yFXtQIILZw79a1bRWsbfFrkWiC9g0JgNDt/pzds2EsTltVS5OWRMaVcrL3glP8+U
                ObW4qzTJiI6m6LKV7hnmaL1fR/NUjWk+fvc/mwECgYAMs3fFP7RT47siLWbwDs+z
                zEyYmNvkNu3lGI6GmCvmh2VUx5qDTDS+Hm+LDCqTqRKdH98b2Vn7LUHOBtE4w6N1
                nhj6ljeOAe/VkTcWdoOyHRS9/RRb+S84o5RuzVtH31SA3pl6FlLJ7Z8d7vBscf6z
                QUlxxENNglL/bh3TPP3rTQKBgC8LwSZ4s1QSb/CaoaBG7Uo4NYWiGA4g5MoedmAJ
                Fcjs5DPRmyT5gg531zR43qZDDKu7eOmjfxKL9sz43rhtTuZO4ZGAutzuaUGWASke
                HS3wo4dbmpdhkVRhc5lqI3OUz41cqmIPG9bpiXiRhs6QoboDmjFoF4R/8gE8RiK5
                xvUBAoGACrghAg+GlJZ/Aogx7wK6b1k8rfcpgIoHxOPiqIgyMgevTT6D6w8D0CqI
                cEVTZ/fm+EaNuMZvxqSG5f19/obLus+VNXvnMYi3qwFAZ5NhKBen12YhIcaZpOh1
                ZSjeYozDCyRmv76q3sqcLrwxnULIcaK0l255ZczzwiUl39Bqe1o=
                -----END RSA PRIVATE KEY-----
                PRIV_KEY
            ),
            [
                'root' => $ca = <<<'CA'
                    -----BEGIN CERTIFICATE-----
                    MIIDezCCAmOgAwIBAgIJAJn2g4MHmUlvMA0GCSqGSIb3DQEBBQUAMFQxGjAYBgNV
                    BAMMEVJvbGxlcnNjYXBlcyBDQXYzMRUwEwYDVQQKDAxSb2xsZXJzY2FwZXMxEjAQ
                    BgNVBAcMCVJvdHRlcmRhbTELMAkGA1UEBhMCTkwwHhcNMTQwMzMwMTQzNjM5WhcN
                    MTgwMzI5MTQzNjM5WjBUMRowGAYDVQQDDBFSb2xsZXJzY2FwZXMgQ0F2MzEVMBMG
                    A1UECgwMUm9sbGVyc2NhcGVzMRIwEAYDVQQHDAlSb3R0ZXJkYW0xCzAJBgNVBAYT
                    Ak5MMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA9G4MAqOU6tgIw2gJ
                    tZVul3Ef6W37fK2p8MooXJmFRNs6QGloy8bkbAG6rLrmPlOpG4LT6jDpiUOgk4IL
                    v0HZr8tSaJCEUaYWYQkc58HqZK0FfVrrzAQC8lVcO2Xl0HehEoPAtVrX+1h2F6/E
                    38xzmlbUo2Ileiy6ur0KjCo+p22fd+NIEwvtbd1uySA5GsyzIq0vqpRHJzihgXXU
                    TIjxdxZqqHjGslT9Ei97XEYErjFrxlwk8lNFUvxE3u2Xhfhy6qNT1CpcPg8pRVHw
                    IdYqn0ApJPxLchfGjuVmcgmnDeTmBtbNGBPw1dsmswm/nvZC8CiDuqgn6PVIhpio
                    Eru22wIDAQABo1AwTjAdBgNVHQ4EFgQUAe/6RHDxw475z5c8niR0o4ZiYn0wHwYD
                    VR0jBBgwFoAUAe/6RHDxw475z5c8niR0o4ZiYn0wDAYDVR0TBAUwAwEB/zANBgkq
                    hkiG9w0BAQUFAAOCAQEA4VMyvK2I2naw+0rm4wu9rRWOoCYuRRchkE+CvFoUDnQq
                    CvWKaQApPA2qud4gA+S743GduzSf4jfAe8yGY3oA+bUAnqupF+8l19b6GcMfEop7
                    LRkeiSxAVrK2hHxGYMdLXBFqBMS5PaG2LT/m1zjk+j5CJVKAtWHlO8sERSyCqa04
                    2wvjlA/ArnZkt8A56kOFeIK2UBOzTozYmW+D5ZkB41JtzquO7Rty/YhVpuOfCoLX
                    HuwXgPLW3fDUFmEFnIMqDCxZA5NEc+1QapjBkC8cU4xPKjIE3Ljm4Nhq0I67ipC1
                    Jzgsmb7yKoigkH/BZ5sm/spdlz3/eXuEtcC6gLfsPA==
                    -----END CERTIFICATE-----
                    CA,
            ]
        );

        $objectManager->assertEntitiesCountWasSaved(2);
        $objectManager->assertEntitiesWereSavedThat(static fn (object $entity) => $entity->getContents() === $cert);
        $objectManager->assertEntitiesWereSavedThat(static fn (object $entity) => $entity->getContents() === $ca);
    }
}
