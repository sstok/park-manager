<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Subdomain\TLS;

use Carbon\Carbon;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CATest extends TestCase
{
    /** @before */
    #[Before]
    public function freezeTime(): void
    {
        Carbon::setTestNow('2020-05-29T14:12:14.000000+0000');
    }

    #[After]
    public function unFreezeTime(): void
    {
        Carbon::setTestNow(null);
    }

    /** @test */
    public function it_provides_information_when_root(): void
    {
        $ca = new CA('x509-information', [
            'subject' => ['commonName' => 'Example Corp CA'],
            '_pubKey' => 'Here\'s the key Robby Hood!',
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            '_validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            '_validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ]);

        self::assertSame('Here\'s the key Robby Hood!', $ca->getPublicKey());
        self::assertSame('sha1WithRSAEncryption', $ca->getSignatureAlgorithm());
        self::assertSame('a52f33ab5dad33e8af695dad33e8af695dad33e8af69', $ca->getFingerprint());
        self::assertSame([$ca], $ca->toTree());
        // self::assertSame(31, $ca->daysUntilExpirationDate());
        self::assertEquals($validFrom, $ca->validFromDate());
        self::assertEquals($validTo, $ca->expirationDate());
        self::assertSame(['commonName' => 'Example Corp CA'], $ca->getIssuer());
        self::assertSame(['commonName' => 'Example Corp CA'], $ca->getSubject());
        self::assertNull($ca->ca);
        self::assertTrue($ca->isValidUntil(Carbon::tomorrow()));
        self::assertFalse($ca->isExpired());
        self::assertTrue($ca->isValid());
        self::assertTrue($ca->isRoot());
    }

    /** @test */
    public function it_provides_information_with_parent_ca(): void
    {
        $rootCA = new CA('CA-x509', [
            'subject' => ['commonName' => 'Example Corp CA'],
            '_pubKey' => 'Here\'s the key Robby Hood!',
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e9af695dad33e8af69',
            '_validFrom' => ($validFrom = Carbon::rawParse('2020-01-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            '_validTo' => ($validTo = Carbon::rawParse('2020-10-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], null);

        $ca = new CA('x509-information', [
            'subject' => ['commonName' => 'Example Corp EV CA'],
            '_pubKey' => 'Here\'s the key Robby!',
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            '_fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af79',
            '_validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            '_validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(\DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], $rootCA);

        self::assertSame('Here\'s the key Robby!', $ca->getPublicKey());
        self::assertSame($rootCA, $ca->getParent());
        self::assertSame($rootCA, $ca->ca);
        self::assertTrue($ca->isValid());
        self::assertFalse($ca->isRoot());
    }

    /** @test */
    public function it_provides_information_for_ca_tree(): void
    {
        $rootCA = self::getCA('Example Corp CA', 'Example Corp CA');
        $ca1 = self::getCA('Example Corp CA EV', 'Example Corp CA', $rootCA);
        $ca2 = self::getCA('Annoying Antivirus TLS Spy', 'Example Corp CA', $ca1);

        self::assertSame([$rootCA, $ca1, $ca2], $ca2->toTree());
    }

    private static function getCA(string $name, string $issuer, CA $parent = null): CA
    {
        return new CA('x509-information', [
            'subject' => ['commonName' => $name],
            '_pubKey' => 'Here\'s the key Robby!',
            '_signatureAlgorithm' => 'sha1WithRSAEncryption',
            'issuer' => ['commonName' => $issuer],
        ], $parent);
    }
}
