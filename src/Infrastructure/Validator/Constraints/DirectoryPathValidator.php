<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DirectoryPathValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (! $constraint instanceof DirectoryPath) {
            throw new UnexpectedTypeException($constraint, DirectoryPath::class);
        }

        if (! \is_scalar($value) && ! (\is_object($value) && \method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        // Actual length is 4096, but some is reserved for the system.
        if (\mb_strlen($value) > 3096) {
            $this->context->buildViolation($constraint->pathToLongMessage)
                ->setInvalidValue($value)
                ->setParameter('{{ max }}', $this->formatValue(3096))
                ->setCode(DirectoryPath::PATH_TO_LONG_ERROR)
                ->addViolation();

            return;
        }

        foreach (\explode('/', \trim($value, '/')) as $chunk) {
            // (File)name length is limited in bytes, not characters.
            if (\mb_strlen($chunk, '8bit') > 255) {
                $this->context->buildViolation($constraint->nameToLongMessage)
                    ->setInvalidValue($chunk)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setParameter('{{ chunk }}', $this->formatValue($chunk))
                    ->setParameter('{{ max }}', $this->formatValue(255))
                    ->setCode(DirectoryPath::NAME_TO_LONG_ERROR)
                    ->addViolation();

                return;
            }

            // Can start with a dot, must not end with a special character. No combined special characters.
            if (! \preg_match('~^\.?\w+(?:\.?[@\w-])*$~u', $chunk) || \preg_match('~(?:[.@-_]$)|[@_.-]{2}~', $chunk)) {
                $this->context->buildViolation($constraint->message)
                    ->setInvalidValue($value)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setParameter('{{ chunk }}', $this->formatValue($chunk))
                    ->setCode(DirectoryPath::INVALID_PATH)
                    ->addViolation();

                return;
            }
        }
    }
}
