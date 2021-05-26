<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\DomainName;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Command\Webhosting\SubDomain\AddSubDomain;
use ParkManager\Application\Command\Webhosting\SubDomain\AddSubDomainHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainAlreadyExists;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Tests\Application\Service\TLS\CertificateFactoryMock;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\DomainName\SubDomainRepositoryMock;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AddSubDomainHandlerTest extends TestCase
{
    private const DOMAIN_ID_1 = '10abb1db-6e93-4dfc-9ba1-cdd46a225657';
    private const DOMAIN_ID_2 = '2c66cf58-4be8-4bfb-a3e4-4f3298790060';

    private OwnerRepositoryMock $ownerRepository;
    private SubDomainRepositoryMock $subDomainRepository;
    private AddSubDomainHandler $handler;

    protected function setUp(): void
    {
        $this->ownerRepository = new OwnerRepositoryMock();

        $space = Space::registerWithCustomConstraints(SpaceId::create(), $this->ownerRepository->getAdminOrganization(), new Constraints());
        $domainNameRepository = new DomainNameRepositoryMock([
            DomainName::registerForSpace(DomainNameId::fromString(self::DOMAIN_ID_1), $space, new DomainNamePair('example', 'com')),
            DomainName::registerForSpace(DomainNameId::fromString(self::DOMAIN_ID_2), $space, new DomainNamePair('dev.rollerscapes', 'net')),
        ]);

        $this->subDomainRepository = new SubDomainRepositoryMock();
        $this->handler = new AddSubDomainHandler($domainNameRepository, $this->subDomainRepository, new CertificateFactoryMock());
    }

    /** @test */
    public function it_handles_new_sub_domain(): void
    {
        $this->handler->__invoke(AddSubDomain::with('26fdb3e9-d8aa-4fa4-a8da-8b889fd86ab6', self::DOMAIN_ID_1, 'blog', 'blog/', ['php-version' => '7.5']));

        $this->subDomainRepository->assertEntitiesCountWasSaved(1);
        $this->subDomainRepository->assertEntityWasSavedThat(
            '26fdb3e9-d8aa-4fa4-a8da-8b889fd86ab6',
            static function (SubDomain $entity) {
                self::assertTrue($entity->host->id->equals(DomainNameId::fromString(self::DOMAIN_ID_1)), 'Host should equal');
                self::assertSame('blog', $entity->name, 'Name should equal');
                self::assertSame('blog/', $entity->homeDir, 'Homedir should equal');
                self::assertSame(['php-version' => '7.5'], $entity->config, 'Config should equal');
                self::assertNull($entity->tlsCert, 'TLSCert should be null');

                return true;
            }
        );
    }

    /** @test */
    public function it_fails_with_already_existing_sub_domain(): void
    {
        $this->handler->__invoke(AddSubDomain::with('26fdb3e9-d8aa-4fa4-a8da-8b889fd86ab6', self::DOMAIN_ID_1, 'blog', 'blog/', ['php-version' => '7.5']));

        $this->expectException(SubDomainAlreadyExists::class);
        $this->expectExceptionMessage('SubDomain "blog.example.com" already exists.');

        // Again
        $this->handler->__invoke(AddSubDomain::with('263220e1-641e-465d-8819-e921b0752b89', self::DOMAIN_ID_1, 'blog', 'blog/', ['php-version' => '7.5']));
    }

    /** @test */
    public function it_handles_new_sub_domain_with_tls(): void
    {
        $command = AddSubDomain::with('26fdb3e9-d8aa-4fa4-a8da-8b889fd86ab6', self::DOMAIN_ID_1, 'blog', 'blog/', ['php-version' => '7.5'])
            ->andTLSInformation(
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
                ])
        ;

        $this->handler->__invoke($command);

        $this->subDomainRepository->assertEntitiesCountWasSaved(1);
        $this->subDomainRepository->assertEntityWasSavedThat(
            '26fdb3e9-d8aa-4fa4-a8da-8b889fd86ab6',
            static function (SubDomain $entity) use ($cert) {
                self::assertTrue($entity->host->id->equals(DomainNameId::fromString(self::DOMAIN_ID_1)), 'Host should equal');
                self::assertSame('blog', $entity->name, 'Name should equal');
                self::assertSame('blog/', $entity->homeDir, 'Homedir should equal');
                self::assertSame(['php-version' => '7.5'], $entity->config, 'Config should equal');
                self::assertNotNull($entity->tlsCert, 'TLSCert should be not null');
                self::assertSame($entity->tlsCert->getContents(), $cert);

                return true;
            }
        );
    }
}
