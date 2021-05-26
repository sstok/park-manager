<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use ParkManager\Infrastructure\Validator\Constraints\DomainNameRegistrable;
use ParkManager\Infrastructure\Validator\Constraints\DomainNameRegistrableValidator;
use ParkManager\Tests\Mock\PdpMockProvider;
use Pdp\Domain;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
final class DomainNameRegistrableValidatorTest extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function createValidator(): DomainNameRegistrableValidator
    {
        return new DomainNameRegistrableValidator(PdpMockProvider::getPdpManager());
    }

    /** @test */
    public function it_ignores_null_and_empty(): void
    {
        $this->validator->validate(null, new DomainNameRegistrable());
        $this->assertNoViolation();

        $this->validator->validate('', new DomainNameRegistrable(allowPrivate: true));
        $this->assertNoViolation();
    }

    /**
     * @test
     * @dataProvider provideAcceptedDomainNames
     */
    public function it_accepts_domain_names_with_known_suffix(string $name): void
    {
        $this->validator->validate($name, new DomainNameRegistrable());
        $this->assertNoViolation();

        $this->validator->validate(Domain::fromIDNA2008($name), new DomainNameRegistrable());
        $this->assertNoViolation();
    }

    public function provideAcceptedDomainNames(): iterable
    {
        yield ['example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    /**
     * @test
     * @dataProvider provideAcceptedPrivateSuffixes
     */
    public function it_accepts_domain_names_with_known_suffix_and_private(string $name): void
    {
        $this->validator->validate($name, new DomainNameRegistrable(allowPrivate: true));
        $this->assertNoViolation();
    }

    public function provideAcceptedPrivateSuffixes(): iterable
    {
        yield ['example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['example.github.io'];
        yield ['github.com'];

        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    /**
     * @test
     * @dataProvider provideRejectedDomainNames
     */
    public function it_rejects_domain_names_with_private_suffix(string $name): void
    {
        $this->validator->validate($name, $constraint = new DomainNameRegistrable());
        $this->buildViolation($constraint->privateMessage)
            ->atPath('property.path.suffix')
            ->setCode(DomainNameRegistrable::PRIVATE_SUFFIX)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    public function provideRejectedDomainNames(): iterable
    {
        yield ['example.github.io'];
    }

    /** @test */
    public function it_rejects_non_registrable_domain_name(): void
    {
        $this->validator->validate('*.example.com', $constraints = new DomainNameRegistrable());
        $this->buildViolation($constraints->message)
            ->setCode(DomainNameRegistrable::NOT_REGISTRABLE)
            ->setInvalidValue('*.example.com')
            ->assertRaised()
        ;
    }

    /**
     * @test
     * @dataProvider provideRegistrableNamesExceedingPath
     */
    public function it_rejects_domain_name_with_path_exceeding_registrable(string $name, string $registrablePart): void
    {
        $this->validator->validate($name, $constraints = new DomainNameRegistrable(allowPrivate: true));
        $this->buildViolation($constraints->lengthMessage)
            ->setParameter('{registrable}', $registrablePart)
            ->setCode(DomainNameRegistrable::REGISTRABLE_LENGTH_EXCEEDED)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    public function provideRegistrableNamesExceedingPath(): iterable
    {
        yield ['example.no.co.uk', 'no.co.uk'];
        yield ['test.example.github.io', 'example.github.io'];
        yield ['dev2.rollerscapes.net', 'rollerscapes.net'];
    }

    /**
     * @test
     * @dataProvider provideWrongFormattedDomains
     */
    public function it_rejects_domain_name_when_failed_to_parse(string $name): void
    {
        $this->validator->validate($name, $constraint = new DomainNameRegistrable());
        $this->buildViolation('This value is not a valid domain-name.')
            ->setCode(DomainNameRegistrable::INVALID_SYNTAX)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    public function provideWrongFormattedDomains(): iterable
    {
        yield ['xn--94823482.nl']; // invalid IDN, which is actually thrown during the resolver phase
        yield ['nope.'];
        yield ['.nope'];
    }
}
