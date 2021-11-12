<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Warning\QuotedString;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class EmailboxNameValidator extends ConstraintValidator
{
    private EmailLexer $lexer;

    public function __construct()
    {
        $this->lexer = new EmailLexer();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! \is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (! $constraint instanceof EmailboxName) {
            throw new UnexpectedTypeException($constraint, EmailboxName::class);
        }

        // Don't allow at-sign in the local part.
        // Although possible this is highly discouraged and risks to many issues.
        //
        // And don't allow labels.
        if (str_contains($value, '@') || str_contains($value, '+')) {
            $this->context->buildViolation('invalid_emailbox_name')
                ->setInvalidValue($value)
                ->addViolation()
            ;

            return;
        }

        $parser = new EmailParser($this->lexer);
        $email = $value . '@example.com';

        try {
            $parser->parse($email);

            foreach ($parser->getWarnings() as $warning) {
                // Quoted strings are acceptable. Comments and others are not.
                if ($warning instanceof QuotedString) {
                    continue;
                }

                $this->context->buildViolation('invalid_emailbox_name')
                    ->setInvalidValue($value)
                    ->setCause($warning)
                    ->addViolation()
                ;

                return;
            }
        } catch (InvalidEmail $e) {
            $this->context->buildViolation('invalid_emailbox_name')
                ->setInvalidValue($value)
                ->setCause($e)
                ->addViolation()
            ;
        }
    }
}
