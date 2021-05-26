<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * @internal
 */
final class EmailAddressTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $value = new EmailAddress('info@example.com');

        self::assertSame('info@example.com', $value->address);
        self::assertSame('info@example.com', $value->toString());
        self::assertSame('info@example.com', $value->canonical);
        self::assertSame('info', $value->local);
        self::assertSame('example.com', $value->domain);
        self::assertNull($value->name);
        self::assertSame('', $value->label);

        $value->validate();
    }

    /** @test */
    public function its_constructable_with_label(): void
    {
        $value = new EmailAddress('info+webmaster@example.com', 'Teacher');

        self::assertSame('info+webmaster@example.com', $value->address);
        self::assertSame('info+webmaster@example.com', $value->toString());
        self::assertSame('info@example.com', $value->canonical);
        self::assertSame('info', $value->local);
        self::assertSame('example.com', $value->domain);
        self::assertSame('Teacher', $value->name);
        self::assertSame('webmaster', $value->label);

        self::assertSame('info+webmaster@example.com', $value->truncate(29));
        self::assertSame('info+webmaster@example.com', $value->truncate(30));
        self::assertSame('info+w...@examp...', $value->truncate(10));
        self::assertSame('info+we...@exampl...', $value->truncate(11));
        self::assertSame('info+webm#@example.#', $value->truncate(10, '#'));
        self::assertSame('info+webm...@example.com', $value->truncate(13));

        $value->validate();
    }

    /** @test */
    public function its_constructable_with_double_at_sign(): void
    {
        $value = new EmailAddress('"info@hello"@example.com');

        self::assertSame('"info@hello"@example.com', $value->address);
        self::assertSame('"info@hello"@example.com', $value->toString());
        self::assertSame('"info@hello"@example.com', $value->canonical);
        self::assertSame('"info@hello"', $value->local);
        self::assertSame('example.com', $value->domain);
        self::assertNull($value->name);
        self::assertSame('', $value->label);
        self::assertFalse($value->isPattern);

        self::assertSame('"info@hello"@example.com', $value->truncate(29));
        self::assertSame('"info@hello"@example.com', $value->truncate(30));
        self::assertSame('"info@...@examp...', $value->truncate(10));

        $value->validate();
    }

    /** @test */
    public function its_constructable_as_pattern(): void
    {
        $value = new EmailAddress('*@example.com');

        self::assertSame('*@example.com', $value->address);
        self::assertSame('*@example.com', $value->toString());
        self::assertSame('*@example.com', $value->canonical);
        self::assertSame('*', $value->local);
        self::assertSame('example.com', $value->domain);
        self::assertNull($value->name);
        self::assertSame('', $value->label);
        self::assertTrue($value->isPattern);

        self::assertSame('*@example.com', $value->truncate(30));
        self::assertSame('*@exa#', $value->truncate(5, '#'));

        $value->validate();
    }

    /** @test */
    public function its_constructable_with_name(): void
    {
        $value = new EmailAddress('info@example.com', 'Janet Doe');

        self::assertSame('info@example.com', $value->address);
        self::assertSame('info@example.com', $value->canonical);
        self::assertSame('Janet Doe', $value->name);
        self::assertSame('', $value->label);
    }

    /** @test */
    public function it_canonicalizes_the_address(): void
    {
        $value = new EmailAddress('infO@EXAMPLE.com');

        self::assertSame('infO@EXAMPLE.com', $value->address);
        self::assertSame('info@example.com', $value->canonical);
        self::assertSame('info', $value->local);
        self::assertSame('example.com', $value->domain);
        self::assertNull($value->name);
        self::assertSame('', $value->label);
    }

    /** @test */
    public function it_canonicalizes_the_address_with_idn(): void
    {
        $value = new EmailAddress('info@xn--tst-qla.de');

        // Note. Original value is not transformed as some IDN TLDs
        // are not supported natively (Emoji for example).
        self::assertSame('info@xn--tst-qla.de', $value->address);
        self::assertSame('info@täst.de', $value->canonical);
        self::assertSame('info', $value->local);
        self::assertSame('täst.de', $value->domain);
        self::assertNull($value->name);
        self::assertSame('', $value->label);
    }

    /** @test */
    public function it_extracts_the_label(): void
    {
        $value = new EmailAddress('info+hello@example.com');

        self::assertSame('info+hello@example.com', $value->address);
        self::assertSame('info@example.com', $value->canonical);
        self::assertSame('info', $value->local);
        self::assertSame('example.com', $value->domain);
        self::assertNull($value->name);
        self::assertSame('hello', $value->label);
    }

    /** @test */
    public function it_validates_basic_formatting(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::missingAtSign('info?example.com'));
        $this->expectExceptionCode(1);

        new EmailAddress('info?example.com');
    }

    /** @test */
    public function it_validates_basic_formatting_empty_domain(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::idnError('info@', \IDNA_ERROR_EMPTY_LABEL));
        $this->expectExceptionCode(2);

        new EmailAddress('info@');
    }

    /** @test */
    public function it_validates_basic_formatting_empty_domain_with_space(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::idnError('info@  ', \IDNA_ERROR_EMPTY_LABEL));
        $this->expectExceptionCode(2);

        new EmailAddress('info@  ');
    }

    /** @test */
    public function it_validates_advanced_formatting(): void
    {
        $this->expectException(RfcComplianceException::class);

        $address = new EmailAddress('"=--WAT--@"=@example.com');
        $address->validate();
    }

    /** @test */
    public function it_validates_idn_format(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::idnError('ok@xn--wat.de', \IDNA_ERROR_INVALID_ACE_LABEL));
        $this->expectExceptionCode(2);

        new EmailAddress('ok@xn--wat.de');
    }

    /** @test */
    public function idn_error_codes(): void
    {
        $message = MalformedEmailAddress::idnError('nope@example.com', \IDNA_ERROR_EMPTY_LABEL | \IDNA_ERROR_LABEL_TOO_LONG)
            ->getMessage()
        ;

        self::assertStringContainsString('a non-final domain name label (or the whole domain name) is empty', $message);
        self::assertStringContainsString('a domain name label is longer than 63 bytes', $message);
        self::assertStringNotContainsString('a label starts with a combining mark', $message);

        self::assertSame(
            'Malformed email address "nope@example.com" (IDN Error reported Unknown IDNA conversion error.)',
            MalformedEmailAddress::idnError('nope@example.com', 0)->getMessage()
        );
    }

    /** @test */
    public function it_validates_pattern_multi_wildcard(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::patternMultipleWildcards('*info*@example.com'));
        $this->expectExceptionCode(3);

        new EmailAddress('*info*@example.com');
    }

    /** @test */
    public function it_validates_pattern_in_label(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::patternWildcardInLabel('info+labeled*@example.com'));
        $this->expectExceptionCode(4);

        new EmailAddress('info+labeled*@example.com');
    }

    /** @test */
    public function its_equatable(): void
    {
        self::assertTrue(($email = new EmailAddress('info+labeled@example.com'))->equals($email));
        self::assertTrue((new EmailAddress('info+labeled@example.com'))->equals(new EmailAddress('info+labeled@example.com')));
        self::assertTrue((new EmailAddress('info+labeled@example.com', 'he'))->equals(new EmailAddress('info+labeled@example.com', 'he')));

        // Different
        self::assertFalse((new EmailAddress('info@example.com'))->equals(new EmailAddress('info+labeled@example.com')));
        self::assertFalse((new EmailAddress('info@example.com'))->equals(new EmailAddress('infO@example.com')));
        self::assertFalse((new EmailAddress('info@example.com', 'hE'))->equals(new EmailAddress('info@example.com', 'he')));
    }
}
