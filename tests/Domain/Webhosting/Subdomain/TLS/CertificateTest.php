<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Subdomain\TLS;

use Assert\AssertionFailedException;
use Carbon\Carbon;
use Lifthill\Component\Common\Test\EntityHydrator;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;
use ParkManager\Domain\Webhosting\SubDomain\TLS\Certificate;
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
            '_pubKey' => 'Here\'s the key Robby!',
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            '_validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            '_validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'example.com'],
        ]);

        self::assertSame('Here\'s the key Robby!', $cert->getPublicKey());
        self::assertSame('private-keep-of-the-7-keys', $cert->getPrivateKey());
        self::assertSame('x509-information', $cert->getContents());
        self::assertSame('sha1WithRSAEncryption', $cert->getSignatureAlgorithm());
        self::assertSame('a52f33ab5dad33e8af695dad33e8af695dad33e8af69', $cert->getFingerprint());
        // self::assertSame(31, $cert->daysUntilExpirationDate());
        self::assertEquals($validFrom, $cert->validFromDate());
        self::assertEquals($validTo, $cert->expirationDate());
        self::assertSame(['commonName' => 'example.com'], $cert->getIssuer());
        self::assertSame('example.com', $cert->getDomain());
        self::assertSame(['example.com'], $cert->getDomains());
        self::assertSame([], $cert->getAdditionalDomains());
        self::assertNull($cert->ca);
        self::assertTrue($cert->isValidUntil(Carbon::tomorrow()));
        self::assertFalse($cert->isExpired());
        self::assertTrue($cert->isValid());
        self::assertTrue($cert->isSelfSigned());
    }

    /** @test */
    public function it_requires_a_ca_unless_self_signed(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('A CA must be provided when the Certificate is not self-signed');

        new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            '_pubKey' => 'Here\'s the key Robby!',
            '_domains' => ['example.com', 'example.net', '*.example.net'],
            'issuer' => ['commonName' => 'Leroy Jenkins Inc. CA'],
        ]);
    }

    /** @test */
    public function it_properly_handles_streamed_content_holders(): void
    {
        $resourceFactory = static function (string $str) {
            $fp = fopen('php://temp', 'rb+');
            \assert(\is_resource($fp));
            fwrite($fp, $str);
            fseek($fp, 0);

            return $fp;
        };

        $object = EntityHydrator::hydrateEntity(Certificate::class)
            ->set('contents', $resourceFactory('x509-information'))
            ->set('publicKey', $resourceFactory('Here\'s the key Robby!'))
            ->set('privateKey', $resourceFactory('private-keep-of-the-7-keys'));

        self::assertSame('x509-information', $object->getEntity()->getContents());
        self::assertSame('Here\'s the key Robby!', $object->getEntity()->getPublicKey());
        self::assertSame('private-keep-of-the-7-keys', $object->getEntity()->getPrivateKey());
    }

    /** @test */
    public function it_provides_information_with_alternative_names(): void
    {
        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            '_pubKey' => 'Here\'s the key Robby!',
            '_domains' => ['example.com', 'example.net'],
            '_alt_domains' => ['example.net'],
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            '_validFrom' => Carbon::rawParse('2020-05-29T14:12:14.000000+0000')->format(\DateTime::RFC2822),
            '_validTo' => Carbon::rawParse('2020-06-29T14:12:14.000000+0000')->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'example.com'],
        ]);

        self::assertSame(['example.net'], $cert->getAdditionalDomains());
    }

    /** @test */
    public function it_provides_information_with_ca(): void
    {
        $ca = new CA('CA-x509', [
            'subject' => ['commonName' => 'Example Corp CA'],
            '_pubKey' => 'Here\'s the key Robby!',
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e9af695dad33e8af69',
            '_validFrom' => ($validFrom = Carbon::rawParse('2020-01-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            '_validTo' => ($validTo = Carbon::rawParse('2020-10-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], null);

        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            '_pubKey' => 'Here\'s the key Robby Hood!',
            '_domains' => ['example.com'],
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            '_validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            '_validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], $ca);

        self::assertSame('Here\'s the key Robby Hood!', $cert->getPublicKey());
        self::assertSame($ca, $cert->ca);
        self::assertTrue($cert->isValid());
        self::assertFalse($cert->isSelfSigned());
    }

    /** @test */
    public function it_allows_checking_if_domain_is_supported(): void
    {
        $cert = new Certificate('x509-information', 'private-keep-of-the-7-keys', [
            'subject' => ['commonName' => 'example.com'],
            '_pubKey' => 'Here\'s the key Robby!',
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

    /**
     * @test
     *
     * @dataProvider provideIt_ensures_all_data_is_providedCases
     */
    public function it_ensures_all_data_is_provided(string $removeKey): void
    {
        $data = [
            'subject' => ['commonName' => 'example.com'],
            '_pubKey' => 'Here\'s the key Robby!',
            '_domains' => ['example.com', 'example.net', '*.example.net'],
            'issuer' => ['commonName' => 'example.com'],
        ];
        unset($data[$removeKey]);

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(sprintf('Array does not contain an element with key "%s"', $removeKey));

        new Certificate('x509-information', 'private-keep-of-the-7-keys', $data);
    }

    /**
     * @return \Generator<int, array{0: string}>
     */
    public static function provideIt_ensures_all_data_is_providedCases(): iterable
    {
        yield ['subject'];

        yield ['_pubKey'];

        yield ['issuer'];
    }
}
