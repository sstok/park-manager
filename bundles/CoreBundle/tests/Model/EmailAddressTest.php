<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Model;

use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Model\Exception\MalformedEmailAddress;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EmailAddressTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $value = new EmailAddress('info@example.com');

        static::assertEquals('info@example.com', $value->address);
        static::assertEquals('info@example.com', $value->toString());
        static::assertEquals('info@example.com', $value->canonical);
        static::assertEquals('', $value->name);
        static::assertEquals('', $value->label);
    }

    /** @test */
    public function its_constructable_with_name(): void
    {
        $value = new EmailAddress('info@example.com', 'Janet Doe');

        static::assertEquals('info@example.com', $value->address);
        static::assertEquals('info@example.com', $value->canonical);
        static::assertEquals('Janet Doe', $value->name);
        static::assertEquals('', $value->label);
    }

    /** @test */
    public function it_canonicalizes_the_address(): void
    {
        $value = new EmailAddress('info@EXAMPLE.com');

        static::assertEquals('info@EXAMPLE.com', $value->address);
        static::assertEquals('info@example.com', $value->canonical);
        static::assertEquals('', $value->name);
        static::assertEquals('', $value->label);
    }

    /** @test */
    public function it_canonicalizes_the_address_with_idn(): void
    {
        $value = new EmailAddress('info@xn--tst-qla.de');

        // Note. Original value is not transformed as some IDN TLDs
        // are not supported natively (Emoji for example).
        static::assertEquals('info@xn--tst-qla.de', $value->address);
        static::assertEquals('info@täst.de', $value->canonical);
        static::assertEquals('', $value->name);
        static::assertEquals('', $value->label);
    }

    /** @test */
    public function it_extracts_the_label(): void
    {
        $value = new EmailAddress('info+hello@example.com');

        static::assertEquals('info+hello@example.com', $value->address);
        static::assertEquals('info@example.com', $value->canonical);
        static::assertEquals('', $value->name);
        static::assertEquals('hello', $value->label);
    }

    /** @test */
    public function it_validates_basic_formatting(): void
    {
        $this->expectException(MalformedEmailAddress::class);
        $this->expectExceptionMessage('Malformed e-mail address "info?example.com" (missing @)');

        new EmailAddress('info?example.com');
    }

    /** @test */
    public function it_validates_idn_format(): void
    {
        $this->expectException(MalformedEmailAddress::class);
        $this->expectExceptionMessageRegExp('/Malformed e-mail address "ok@xn--wat\.de" \(IDN Error reported \d+\)/');

        new EmailAddress('ok@xn--wat.de');
    }
}
