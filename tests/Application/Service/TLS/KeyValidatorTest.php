<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service\TLS;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\TLS\KeyValidator;
use ParkManager\Application\Service\TLS\Violation\KeyBitsToLow;
use ParkManager\Application\Service\TLS\Violation\PublicKeyMismatch;
use ParkManager\Application\Service\TLS\Violation\UnprocessableKey;
use ParkManager\Application\Service\TLS\Violation\UnprocessablePEM;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class KeyValidatorTest extends TestCase
{
    /** @test */
    public function it_fails_with_invalid_private_key(): void
    {
        $validator = new KeyValidator();

        $this->expectException(UnprocessableKey::class);
        $this->expectExceptionMessage('Unable to read private key-data');

        $validator->validate(
            new HiddenString('-----BEGIN RSA PRIVATE KEY-----NOPE NOPE-----END RSA PRIVATE KEY-----'),
            <<<'CERT'
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
                CERT
        );
    }

    /** @test */
    public function it_fails_with_invalid_cert(): void
    {
        $validator = new KeyValidator();

        $this->expectException(UnprocessablePEM::class);

        $validator->validate(
            new HiddenString(
                $privateKey = <<<'PRIV_KEY'
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
            <<<'CERT'
                -----BEGIN CERTIFICATE-----
                MIIDKzCCAhMCCQDZHE66hI+pmjANBgkqhkiG9w0BAQUFADBUMRowGAYDVQQDDBFS
                -----END CERTIFICATE-----
                CERT
        );
    }

    /** @test */
    public function it_fails_with_key_mismatch(): void
    {
        $validator = new KeyValidator();

        $this->expectException(PublicKeyMismatch::class);

        $validator->validate(
            new HiddenString(
                $privateKey = <<<'PRIV_KEY'
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
            <<<'CERT'
                -----BEGIN CERTIFICATE-----
                MIIHGTCCBgGgAwIBAgIQBh3eOmYhdHQ4TTZVG+hHijANBgkqhkiG9w0BAQsFADBN
                MQswCQYDVQQGEwJVUzEVMBMGA1UEChMMRGlnaUNlcnQgSW5jMScwJQYDVQQDEx5E
                aWdpQ2VydCBTSEEyIFNlY3VyZSBTZXJ2ZXIgQ0EwHhcNMTgwMjA4MDAwMDAwWhcN
                MjEwMjEyMTIwMDAwWjBpMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNV
                BAcTDVNhbiBGcmFuY2lzY28xITAfBgNVBAoTGFNsYWNrIFRlY2hub2xvZ2llcywg
                SW5jLjESMBAGA1UEAxMJc2xhY2suY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A
                MIIBCgKCAQEAqb0QCgBUkwHwC1AUT1N1W6wfbKSUZGSQ9Pf7EovdVIt1f8hrq5KZ
                OvVUaU/5qsS9UMm1GGqhjVrFqRKv//rZ/VaIThNaLVGQ3yTWCmnPxTZBvEOH1oLP
                i2V+XgDcX2drRUUfFELQy8EZVABwQu5Y3FluB1S7Nv1EH2tOsug5koMIdtMetUo/
                nKPzpuVC/4C/8oPN3+37cSriAImfxrifrrSCLkMscnwh6VcSuajnlCgw/iVcQzEE
                0OGht+KmFgIvjTWmKLx44MvkKqPUnvBudKk4k+9V527g9uNM0rxCVXWb1hf5w08I
                VvEC5/N78HrBl/q/e2oaygp95z/CQ5aJqQIDAQABo4ID1zCCA9MwHwYDVR0jBBgw
                FoAUD4BhHIIxYdUvKOeNRji0LOHG2eIwHQYDVR0OBBYEFPla7+E8XELNsM7Mg46q
                uGwJyd0tMCEGA1UdEQQaMBiCCXNsYWNrLmNvbYILKi5zbGFjay5jb20wDgYDVR0P
                AQH/BAQDAgWgMB0GA1UdJQQWMBQGCCsGAQUFBwMBBggrBgEFBQcDAjBrBgNVHR8E
                ZDBiMC+gLaArhilodHRwOi8vY3JsMy5kaWdpY2VydC5jb20vc3NjYS1zaGEyLWc2
                LmNybDAvoC2gK4YpaHR0cDovL2NybDQuZGlnaWNlcnQuY29tL3NzY2Etc2hhMi1n
                Ni5jcmwwTAYDVR0gBEUwQzA3BglghkgBhv1sAQEwKjAoBggrBgEFBQcCARYcaHR0
                cHM6Ly93d3cuZGlnaWNlcnQuY29tL0NQUzAIBgZngQwBAgIwfAYIKwYBBQUHAQEE
                cDBuMCQGCCsGAQUFBzABhhhodHRwOi8vb2NzcC5kaWdpY2VydC5jb20wRgYIKwYB
                BQUHMAKGOmh0dHA6Ly9jYWNlcnRzLmRpZ2ljZXJ0LmNvbS9EaWdpQ2VydFNIQTJT
                ZWN1cmVTZXJ2ZXJDQS5jcnQwDAYDVR0TAQH/BAIwADCCAfYGCisGAQQB1nkCBAIE
                ggHmBIIB4gHgAHYApLkJkLQYWBSHuxOizGdwCjw1mAT5G9+443fNDsgN3BAAAAFh
                d2Q95wAABAMARzBFAiEA42uacv79w94og76vu/L9nzZJAsU0398rJZuBAY8EY30C
                IFCuAzawnV4AOtOEEp7ybdy/0SLBgZ7bBO3gs0EhkOYCAHYAh3W/51l8+IxDmV+9
                827/Vo1HVjb/SrVgwbTq/16ggw8AAAFhd2Q9zQAABAMARzBFAiBIhbiWxOmsFEmC
                2I6ZBg8Qb+xSIv0AgqZTnIHSzaR0BwIhALoijpGV0JB2xBgW88noxeHdCeqWXQ/a
                HPDAd/Q37M+WAHYAu9nfvB+KcbWTlCOXqpJ7RzhXlQqrUugakJZkNo4e0YUAAAFh
                d2Q+IAAABAMARzBFAiEA0p6Cq67EzeVhxYSpNJYU8Ys7Pj9c4EQPmPaAvnLDL0wC
                IBnOHO2DWoBi+LH6Z/uicH+4nbb4S15zV96NqFn9mXH0AHYAb1N2rDHwMRnYmQCk
                URX/dxUcEdkCwQApBo2yCJo32RMAAAFhd2Q/4AAABAMARzBFAiEA2C3VUu67nO5T
                e2Q8okaIkPftUdE+GHyKkZbqmJMg550CIBFZW53z4BUmtP4GDBEA85D/EnDBPOx2
                OC6cgoRW7sz/MA0GCSqGSIb3DQEBCwUAA4IBAQBUh0yybzRV4ednO+RM4uifnBkf
                S/9r4IHqvFyYgyofd1hygwD3i/pT10V+yF2teqL/FuwsInbjrvGpwFH/uiuhGgzc
                hJ5TOA0/+A/RYNo7sN7An9NBYvedJOlV0iDUhVuQpGefEY3VHqtg0qNu9YoAAl67
                pDCmmQQoNKHDdq2IFq8taF8ros+stqC+cPBipVLxXe9wAFnTkjq0VjB1VqKzLDQ+
                VGN9QV+gw0KI7opJ4K/UKOTnG7ON0zlKIqAK2pXUVsQa9Q5kMbakOk3930bGrkXW
                dqEt/Oc2qDvj/OFnFvaAiKhWUmwhu3IJT4B+W15sPYYBAC4N4FhjP+aGv6IK
                -----END CERTIFICATE-----
                CERT
        );
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function it_accepts_valid_data(): void
    {
        $validator = new KeyValidator();
        $validator->validate(
            new HiddenString(
                $privateKey = <<<'PRIV_KEY'
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
            <<<'CERT'
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
                CERT
        );
    }

    /** @test */
    public function it_fails_with_low_bit_count(): void
    {
        $validator = new KeyValidator();

        $this->expectException(KeyBitsToLow::class);

        $validator->validate(
            new HiddenString(
                $privateKey = <<<'PRIV_KEY'
                    -----BEGIN RSA PRIVATE KEY-----
                    MIICXQIBAAKBgQDGdG43UwH+zGYaPX6TxGglJh9/YRZx2H40nZ5Z05z5nkJXDI7k
                    rrcs/fBLJRgi9AIcdueLLaq1cSp2rwlMVOYOXpMg1MKnXb2fYm0s1/7283ZzoylL
                    1872IJLPmm2Hw5wnNldqTHU9YfOlUEpqSHNV6WNsYr7GTjGfCSSec34d4wIDAQAB
                    AoGAMCZ9u2SjfkvflgxHkti7oA/Q4poO1Q5/CIsZqZfDZXk1hWNhpDCT9xGh5Mma
                    QpjLjlZ3NXieC6nqcKNlcSTEMFizRXpvfDnG7IANuBdp77eoZHhheTLY5yKxhzfN
                    ZBokcgAGcOKPNkCZ6ARb2sMvwm92KK8OhwGC38bq0YRu3bECQQDxskH4LqPw7px2
                    5UdNWvnAmBwpmW9GsIjs3W8lH003hdrZ67//qAI+tVgA2SkFM382PQ/mEjQ65B48
                    N7Ct3/6ZAkEA0jMOZHWjjC8zwCOLn4FXL51YcclojEFQkThx+CMp7H+3Xhqa6KT4
                    CZgYf9Llq9s0wA1bMhCpMZ7JEzTzlHF52wJBAMEn247S/1Ox7bsbGvOYLBaduYwJ
                    QiO1O4hIouWA8X3Y7IDR5jwTcc/Zrz3mTuEIObcH76fHjpQt8HfhbcJXS6kCQQCG
                    IyrGFQQ/S0f9DzHkogdfTUvJoTvkdTHS2nBwZxAz6fS8SsIcQFpA1RydRZpnJ0Xs
                    YRmXQ2aVUb0DUsE2M4wNAkAfJiHdKiDDwUU7YkONkpNLn1eFRyylDsEzdCB2VeSR
                    wGZWY+ujFtu9MYnSf9N14DdZInHfVen8xBpX+xDeKS9E
                    -----END RSA PRIVATE KEY-----
                    PRIV_KEY
            ),
            <<<'CERT'
                -----BEGIN CERTIFICATE-----
                MIICEzCCAXwCCQCmaz9evnQZlzANBgkqhkiG9w0BAQsFADBOMRQwEgYDVQQDDAtl
                eGFtcGxlLmNvbTEQMA4GA1UECgwHRXhhbXBsZTEXMBUGA1UEBwwOV2FzaGhpbmd0
                b24gQUMxCzAJBgNVBAYTAlVTMB4XDTIwMDUwMzEzMTMzNloXDTI0MDUwMjEzMTMz
                NlowTjEUMBIGA1UEAwwLZXhhbXBsZS5jb20xEDAOBgNVBAoMB0V4YW1wbGUxFzAV
                BgNVBAcMDldhc2hoaW5ndG9uIEFDMQswCQYDVQQGEwJVUzCBnzANBgkqhkiG9w0B
                AQEFAAOBjQAwgYkCgYEAxnRuN1MB/sxmGj1+k8RoJSYff2EWcdh+NJ2eWdOc+Z5C
                VwyO5K63LP3wSyUYIvQCHHbniy2qtXEqdq8JTFTmDl6TINTCp129n2JtLNf+9vN2
                c6MpS9fO9iCSz5pth8OcJzZXakx1PWHzpVBKakhzVeljbGK+xk4xnwkknnN+HeMC
                AwEAATANBgkqhkiG9w0BAQsFAAOBgQCs+IaMmYEK9+/xcGjA0xAVCeLGurd1EFT/
                PinGAxLF+3xURpECwQhESASj5yDVzEz4A3RBLoel3mSFVqUqxJceo6X0gE0TwvtL
                xKOFZRRuMCEI7mqHBykhMJIYWBesg3+Uh02u0olK7+CkdSrmSZd8CZtm+imCCaYr
                jSHjiAFydw==
                -----END CERTIFICATE-----
                CERT
        );
    }
}
