<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Warning\Comment;
use ParkManager\Infrastructure\Validator\Constraints\EmailboxName;
use ParkManager\Infrastructure\Validator\Constraints\EmailboxNameValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
final class EmailboxNameValidatorTest extends ConstraintValidatorTestCase
{
    use ConstraintViolationComparatorTrait;

    protected function createValidator(): EmailboxNameValidator
    {
        return new EmailboxNameValidator();
    }

    /** @test */
    public function it_ignores_empty_values(): void
    {
        $this->validator->validate(null, new EmailboxName());
        $this->validator->validate('', new EmailboxName());

        $this->assertNoViolation();
    }

    /** @test */
    public function it_passes_with_values_values(): void
    {
        $this->validator->validate('sysadmin', new EmailboxName());
        $this->validator->validate('áÇ', new EmailboxName());
        $this->validator->validate('"department of departments"', new EmailboxName());
        $this->validator->validate('"[department] of departments]"', new EmailboxName());

        $this->assertNoViolation();
    }

    /** @test */
    public function it_fails_with_at_sign_in_value(): void
    {
        $this->validator->validate($value = 'hello@home', new EmailboxName());

        $this->buildViolation('invalid_emailbox_name')
            ->setInvalidValue($value)
            ->assertRaised()
        ;
    }

    /** @test */
    public function it_fails_with_label(): void
    {
        $this->validator->validate($value = 'hello+ok', new EmailboxName());

        $this->buildViolation('invalid_emailbox_name')
            ->setInvalidValue($value)
            ->assertRaised()
        ;
    }

    /** @test */
    public function it_fails_on_invalid_syntax(): void
    {
        $this->validator->validate($value = '[hello', new EmailboxName());

        $this->buildViolation('invalid_emailbox_name')
            ->setInvalidValue($value)
            ->setCause(new ExpectingATEXT())
            ->assertRaised()
        ;
    }

    /** @test */
    public function it_fails_with_commented_address(): void
    {
        $this->validator->validate($value = 'example(examplecomment)', new EmailboxName());

        $this->buildViolation('invalid_emailbox_name')
            ->setInvalidValue($value)
            ->setCause(new Comment())
            ->assertRaised()
        ;
    }
}
