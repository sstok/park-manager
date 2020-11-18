<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Subdomain\TLS;

use Carbon\Carbon;
use DateTime;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CATest extends TestCase
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
    public function it_provides_information_when_root(): void
    {
        $ca = new CA('x509-information', [
            'subject' => ['commonName' => 'Example Corp CA'],
            'pubKey' => 'Here\'s the key Robby Hood!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af69',
            'validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ]);

        self::assertEquals('Here\'s the key Robby Hood!', $ca->getPublicKey());
        self::assertEquals('sha1WithRSAEncryption', $ca->getSignatureAlgorithm());
        self::assertEquals('a52f33ab5dad33e8af695dad33e8af695dad33e8af69', $ca->getFingerprint());
        self::assertSame([$ca], $ca->toTree());
        self::assertEquals(31, $ca->daysUntilExpirationDate());
        self::assertEquals($validFrom, $ca->validFromDate());
        self::assertEquals($validTo, $ca->expirationDate());
        self::assertEquals(['commonName' => 'Example Corp CA'], $ca->getIssuer());
        self::assertEquals(['commonName' => 'Example Corp CA'], $ca->getSubject());
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
            'pubKey' => 'Here\'s the key Robby Hood!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e9af695dad33e8af69',
            'validFrom' => ($validFrom = Carbon::rawParse('2020-01-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'validTo' => ($validTo = Carbon::rawParse('2020-10-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], null);

        $ca = new CA('x509-information', [
            'subject' => ['commonName' => 'Example Corp EV CA'],
            'pubKey' => 'Here\'s the key Robby!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'fingerprint' => 'a52f33ab5dad33e8af695dad33e8af695dad33e8af79',
            'validFrom' => ($validFrom = Carbon::rawParse('2020-05-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'validTo' => ($validTo = Carbon::rawParse('2020-06-29T14:12:14.000000+0000'))->format(DateTime::RFC2822),
            'issuer' => ['commonName' => 'Example Corp CA'],
        ], $rootCA);

        self::assertEquals('Here\'s the key Robby!', $ca->getPublicKey());
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

    private static function getCA(string $name, string $issuer, ?CA $parent = null): CA
    {
        return new CA('x509-information', [
            'subject' => ['commonName' => $name],
            'pubKey' => 'Here\'s the key Robby!',
            'signatureAlgorithm' => 'sha1WithRSAEncryption',
            'issuer' => ['commonName' => $issuer],
        ], $parent);
    }
}
