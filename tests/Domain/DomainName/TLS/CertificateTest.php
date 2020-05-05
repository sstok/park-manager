<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\DomainName\TLS;

use Carbon\Carbon;
use DateTime;
use ParkManager\Domain\DomainName\TLS\CA;
use ParkManager\Domain\DomainName\TLS\Certificate;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CertificateTest extends TestCase
{
    /** @before */
    public function freezeTime(): void
    {
        Carbon::setTestNow('2020-05-29T14:12:14.000000+0000');
    }

    /** @after */
    public function unFreezeTime(): void
    {
        Carbon::setTestNow(null);
    }

    /** @test */
    public function it_provides_information_as_self_signed(): void
    {
        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            '_domains' => ['example.com'],
            'pubKey' => 'Here\'s the key Robby!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            'validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'example.com'],
        ]);

        self::assertEquals('Here\'s the key Robby!', $cert->getPublicKey());
        self::assertEquals('sha1WithRSAEncryption', $cert->getSignatureAlgorithm());
        self::assertEquals('a52f33ab5dad33e8af695dad33e8af695dad33e8af69', $cert->getFingerprint());
        self::assertEquals(31, $cert->daysUntilExpirationDate());
        self::assertEquals($validFrom, $cert->validFromDate());
        self::assertEquals($validTo, $cert->expirationDate());
        self::assertEquals(['commonName' => 'example.com'], $cert->getIssuer());
        self::assertEquals('example.com', $cert->getDomain());
        self::assertEquals(['example.com'], $cert->getDomains());
        self::assertEquals([], $cert->getAdditionalDomains());
        self::assertNull($cert->ca);
        self::assertTrue($cert->isValidUntil(Carbon::tomorrow()));
        self::assertFalse($cert->isExpired());
        self::assertTrue($cert->isValid());
        self::assertTrue($cert->isSelfSigned());
    }

    /** @test */
    public function it_provides_public_key_when_provided_as_stream(): void
    {
        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            'pubKey' => 'Here\'s the key Robby!',
            '_domains' => ['example.com'],
            'issuer' => ['commonName' => 'example.com'],
        ]);

        (function (): void {
            $fp = \fopen('php://temp', 'rb+');
            \assert(\is_resource($fp));
            \fwrite($fp, 'x509-information2');
            \fseek($fp, 0);

            $this->publicKey = $fp;
            $this->publicKeyString = null;
        })->call($cert);

        self::assertEquals('x509-information2', $cert->getPublicKey());
    }

    /** @test */
    public function it_provides_information_with_alternative_names(): void
    {
        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            'pubKey' => 'Here\'s the key Robby!',
            '_domains' => ['example.com', 'example.net'],
            'altNames' => ['example.net'],
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            'validFrom' => Carbon::rawParse('2020-05-29T14:12:14.000000+0000')->format(DateTime::RFC2822),
            'validTo' => Carbon::rawParse('2020-06-29T14:12:14.000000+0000')->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'example.com'],
        ]);

        self::assertEquals(['example.net'], $cert->getAdditionalDomains());
    }

    /** @test */
    public function it_provides_information_with_ca(): void
    {
        $ca = new CA('CA-x509', [
            'subject' => ['commonName' => 'Example Corp CA'],
            'pubKey' => 'Here\'s the key Robby!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e9af695dad33e8af69',
            'validFrom' => ($validFrom = Carbon::rawParse('2020-01-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'validTo' => ($validTo = Carbon::rawParse('2020-10-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], null);

        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            'pubKey' => 'Here\'s the key Robby Hood!',
            '_domains' => ['example.com'],
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            'validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], $ca);

        self::assertEquals('Here\'s the key Robby Hood!', $cert->getPublicKey());
        self::assertSame($ca, $cert->ca);
        self::assertTrue($cert->isValid());
        self::assertFalse($cert->isSelfSigned());
    }

    /** @test */
    public function it_allows_checking_if_domain_is_supported(): void
    {
        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            'pubKey' => 'Here\'s the key Robby!',
            '_domains' => ['example.com', 'example.net', '*.example.net'],
            'issuer' => ['commonName' => 'example.com'],
        ]);

        self::assertTrue($cert->supportsDomain('example.net'));
        self::assertTrue($cert->supportsDomain('example.com'));
        self::assertTrue($cert->supportsDomain('hello.example.net'));
        self::assertTrue($cert->supportsDomain('*.example.net'));
        self::assertFalse($cert->supportsDomain('*.com'));
        self::assertFalse($cert->supportsDomain('*.example.com'));
        self::assertFalse($cert->supportsDomain('*.he.example.net'));
        self::assertFalse($cert->supportsDomain('*'));
    }
}
