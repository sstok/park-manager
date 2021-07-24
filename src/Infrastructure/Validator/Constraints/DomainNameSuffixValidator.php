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
use Pdp\Idna;
use Pdp\IdnaInfo;
use Pdp\SyntaxError;
use Stringable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DomainNameSuffixValidator extends ConstraintValidator
{
    /**
     * https://tools.ietf.org/html/rfc2606.
     */
    private const RESERVED_TLDS = [
        'example',
        'invalid',
        'localhost',
        'test',
    ];

    public function __construct(private PdpManager $pdpManager)
    {
    }

    /**
     * @param Domain|string|Stringable|null $value
     * @param DomainNameSuffix              $constraint
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

        if (! $constraint instanceof DomainNameSuffix) {
            throw new UnexpectedTypeException($constraint, DomainNameSuffix::class);
        }

        try {
            $domainName = Domain::fromIDNA2008($value);
            $valueStr = $domainName->toString();

            if (str_ends_with($valueStr, '.')) {
                throw SyntaxError::dueToMalformedValue($valueStr);
            }

            $this->validateIdn($valueStr);

            $resolvedDomainName = $this->pdpManager->getPublicSuffixList()->resolve($domainName)->toUnicode();
        } catch (SyntaxError $e) {
            $this->context->buildViolation('This value is not a valid domain-name.')
                ->setCode(DomainNameSuffix::INVALID_SYNTAX)
                ->setInvalidValue($value)
                ->setCause($e)
                ->addViolation()
            ;

            return;
        }

        if (! $resolvedDomainName->suffix()->isKnown() || \in_array($domainName->label(0), self::RESERVED_TLDS, true)) {
            $this->context->buildViolation('This value does not contain a valid domain-name suffix.')
                ->atPath('suffix')
                ->setCode(\in_array($domainName->label(0), self::RESERVED_TLDS, true) ? DomainNameSuffix::RESERVED_TLD_USED : DomainNameSuffix::UNKNOWN_SUFFIX)
                ->setInvalidValue($value)
                ->addViolation()
            ;

            return;
        }

        if ($constraint->requireICANN && ! $resolvedDomainName->suffix()->isICANN()) {
            $this->context->buildViolation('This value does not contain a domain-name suffix that is supported by ICANN.')
                ->atPath('suffix')
                ->setCode(DomainNameSuffix::ICANN_UNKNOWN)
                ->setInvalidValue($value)
                ->addViolation()
            ;
        }
    }

    private function validateIdn(string $valueStr): void
    {
        if (! str_contains($valueStr, 'xn--')) {
            return;
        }

        /** @param-out array{errors: int, isTransitionalDifferent: bool, result: string} $idnaInfo */
        idn_to_utf8($valueStr, Idna::IDNA2008_UNICODE, \INTL_IDNA_VARIANT_UTS46, $idnaInfo);
        $info = IdnaInfo::fromIntl($idnaInfo);

        if ($info->errors() > 0) {
            throw SyntaxError::dueToIDNAError($valueStr, $info);
        }
    }
}
