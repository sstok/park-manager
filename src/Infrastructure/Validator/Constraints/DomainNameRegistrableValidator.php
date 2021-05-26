<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use ParkManager\Application\Service\PdpManager;
use Pdp\Domain;
use Pdp\SyntaxError;
use Stringable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DomainNameRegistrableValidator extends ConstraintValidator
{
    private PdpManager $pdpManager;

    public function __construct(PdpManager $pdpManager)
    {
        $this->pdpManager = $pdpManager;
    }

    /**
     * @param Domain|string|Stringable|null $value
     * @param DomainNameRegistrable         $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_scalar($value)
            && ! $value instanceof Domain
            && ! (\is_object($value) && method_exists($value, '__toString'))
        ) {
            throw new UnexpectedValueException($value, implode('|', ['string', Domain::class]));
        }

        if (! $constraint instanceof DomainNameRegistrable) {
            throw new UnexpectedTypeException($constraint, DomainNameRegistrable::class);
        }

        try {
            $domainName = Domain::fromIDNA2008($value);

            if (str_ends_with($domainName->toString(), '.')) {
                throw SyntaxError::dueToMalformedValue($value);
            }

            $resolvedDomainName = $this->pdpManager->getPublicSuffixList()->resolve($domainName)->toUnicode();
        } catch (SyntaxError $e) {
            $this->context->buildViolation('This value is not a valid domain-name.')
                ->setCode(DomainNameRegistrable::INVALID_SYNTAX)
                ->setInvalidValue((string) $value)
                ->setCause($e)
                ->addViolation()
            ;

            return;
        }

        if (str_contains($domainName->toString(), '*')) {
            $this->context->buildViolation($constraint->message)
                ->setCode(DomainNameRegistrable::NOT_REGISTRABLE)
                ->setInvalidValue((string) $value)
                ->addViolation()
            ;

            return;
        }

        if ($resolvedDomainName->registrableDomain()->toString() !== $resolvedDomainName->toString()) {
            $this->context->buildViolation($constraint->lengthMessage, ['{registrable}' => $resolvedDomainName->registrableDomain()->toString()])
                ->setCode(DomainNameRegistrable::REGISTRABLE_LENGTH_EXCEEDED)
                ->setInvalidValue((string) $value)
                ->addViolation()
            ;

            return;
        }

        if (! $constraint->allowPrivate && $resolvedDomainName->suffix()->isPrivate()) {
            $this->context->buildViolation($constraint->privateMessage)
                ->atPath('suffix')
                ->setCode(DomainNameRegistrable::PRIVATE_SUFFIX)
                ->setInvalidValue((string) $value)
                ->addViolation()
            ;
        }
    }
}
