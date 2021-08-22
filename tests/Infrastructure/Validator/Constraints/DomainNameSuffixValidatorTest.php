<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use Generator;
use ParkManager\Infrastructure\Validator\Constraints\DomainNameSuffix;
use ParkManager\Infrastructure\Validator\Constraints\DomainNameSuffixValidator;
use ParkManager\Tests\Mock\PdpMockProvider;
use Pdp\Domain;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
final class DomainNameSuffixValidatorTest extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function createValidator(): DomainNameSuffixValidator
    {
        return new DomainNameSuffixValidator(PdpMockProvider::getPdpManager());
    }

    /** @test */
    public function it_ignores_null_and_empty(): void
    {
        $this->validator->validate(null, new DomainNameSuffix());
        $this->assertNoViolation();

        $this->validator->validate('', new DomainNameSuffix(requireICANN: false));
        $this->assertNoViolation();
    }

    /**
     * @test
     * @dataProvider provideAcceptedDomainNames
     */
    public function it_accepts_domain_names_with_known_suffix(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->assertNoViolation();

        $this->validator->validate(Domain::fromIDNA2008($name), new DomainNameSuffix());
        $this->assertNoViolation();
    }

    /**
     * @return Generator<int, array{0: string}>
     */
    public function provideAcceptedDomainNames(): Generator
    {
        yield ['example.com'];
        yield ['example.com'];
        yield ['*.example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    /**
     * @test
     * @dataProvider provideAcceptedNoNICANNDomainNames
     */
    public function it_accepts_domain_names_with_known_suffix_and_no_icann_requirement(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix(requireICANN: false));
        $this->assertNoViolation();
    }

    /**
     * @return Generator<int, array{0: string}>
     */
    public function provideAcceptedNoNICANNDomainNames(): Generator
    {
        yield ['example.com'];
        yield ['example.com'];
        yield ['*.example.com'];
        yield ['example.net'];
        yield ['example.co.uk'];
        yield ['example.github.io']; // While valid, this domain is not registrable. Thus non-ICANN

        yield ['faß.de']; // Unicode
        yield ['xn--fa-hia.de']; // Puny-code
    }

    /**
     * @test
     * @dataProvider provideRejectedDomainNames
     */
    public function it_rejects_domain_names_with_unknown_suffix(string $name, string $code = DomainNameSuffix::UNKNOWN_SUFFIX): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->buildViolation('This value does not contain a valid domain-name suffix.')
            ->atPath('property.path.suffix')
            ->setCode($code)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    /**
     * @return Generator<int, array{0: string}|array{0: string, 1: string}>
     */
    public function provideRejectedDomainNames(): Generator
    {
        yield ['example.cong'];
        yield ['example.co.urk'];

        // Reserved.
        yield ['example.example', DomainNameSuffix::RESERVED_TLD_USED];
        yield ['example.localhost', DomainNameSuffix::RESERVED_TLD_USED];
        yield ['example.test', DomainNameSuffix::RESERVED_TLD_USED];
    }

    /**
     * @test
     * @dataProvider provideRejectedDomainNames
     */
    public function it_rejects_domain_names_with_unknown_suffix_and_no_icann(string $name, string $code = DomainNameSuffix::UNKNOWN_SUFFIX): void
    {
        $this->validator->validate($name, new DomainNameSuffix(requireICANN: false));
        $this->buildViolation('This value does not contain a valid domain-name suffix.')
            ->atPath('property.path.suffix')
            ->setCode($code)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    /**
     * @test
     * @dataProvider provideRejectedICANNDomainNames
     */
    public function it_rejects_domain_name_when_icann_is_required_but_not_supported_by_domain(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->buildViolation('This value does not contain a domain-name suffix that is supported by ICANN.')
            ->atPath('property.path.suffix')
            ->setCode(DomainNameSuffix::ICANN_UNKNOWN)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    /**
     * @return Generator<int, array{0: string}>
     */
    public function provideRejectedICANNDomainNames(): Generator
    {
        yield ['example.github.io']; // Private suffix-registration.
    }

    /**
     * @test
     * @dataProvider provideWrongFormattedDomains
     */
    public function it_rejects_domain_name_when_failed_to_parse(string $name): void
    {
        $this->validator->validate($name, new DomainNameSuffix());
        $this->buildViolation('This value is not a valid domain-name.')
            ->setCode(DomainNameSuffix::INVALID_SYNTAX)
            ->setInvalidValue($name)
            ->assertRaised()
        ;
    }

    /**
     * @return Generator<int, array{0: string}>
     */
    public function provideWrongFormattedDomains(): Generator
    {
        yield ['xn--94823482.nl']; // invalid IDN, which is actually thrown during the resolver phase
        yield ['nope.'];
        yield ['.nope'];
    }
}
