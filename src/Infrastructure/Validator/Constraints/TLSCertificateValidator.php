<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use ParkManager\Application\Service\TLS\Violation;
use ParkManager\Domain\Exception\TranslatableException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class TLSCertificateValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint): void
    {
        $this->checkConstraintType($constraint);

        if ($value === null) {
            return;
        }

        if (! $value instanceof X509CertificateBundle) {
            throw new UnexpectedValueException($value, X509CertificateBundle::class);
        }

        try {
            $this->validateTLS($value, $constraint);
        } catch (Violation | TranslatableException $violation) {
            $this->context->buildViolation($violation->getTranslatorId())
                ->setParameters($this->getTranslationArguments($violation))
                ->setInvalidValue($value->certificate)
                ->setCause($violation)
                ->addViolation()
            ;
        }
    }

    abstract protected function checkConstraintType(Constraint $constraint): void;

    abstract protected function validateTLS(X509CertificateBundle $value, Constraint $constraint): void;

    /**
     * @return array<string, mixed>
     */
    private function getTranslationArguments(TranslatableException $violation): array
    {
        $arguments = $violation->getTranslationArgs();

        foreach ($arguments as $key => $v) {
            unset($arguments[$key]);

            if (\is_string($v) && strncmp($key, '@', 1) === 0) {
                $key = mb_substr($key, 1);
                $v = $this->translator->trans($v);
            }

            $arguments[sprintf('{%s}', $key)] = $v;
        }

        return $arguments;
    }
}
