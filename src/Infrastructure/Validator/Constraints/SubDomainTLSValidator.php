<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use ParkManager\Application\Command\Webhosting\SubDomain\SubDomainCommand;
use ParkManager\Domain\DomainName\DomainNameRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SubDomainTLSValidator extends ConstraintValidator
{
    private DomainNameRepository $domainNameRepository;

    public function __construct(DomainNameRepository $domainNameRepository)
    {
        $this->domainNameRepository = $domainNameRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (! $constraint instanceof SubDomainTLS) {
            throw new UnexpectedTypeException($constraint, SubDomainTLS::class);
        }

        if (! $value instanceof SubDomainCommand) {
            throw new UnexpectedValueException($value, SubDomainCommand::class);
        }

        if ($value->certificate === null) {
            return;
        }

        $domainName = $this->domainNameRepository->get($value->domainNameId);
        $tlsBundle = new X509CertificateBundle($value->certificate, $value->privateKey, $value->caList);

        if ($value->name === '@') {
            $requiredName = $domainName->toString();
        } else {
            $requiredName = sprintf('%s.%s', $value->name, $domainName->toString());
        }

        $context = $this->context;
        $validator = $context->getValidator()->inContext($context);
        $validator->validate($tlsBundle, new Sequentially([
            new NotNull(),
            new X509Certificate(),
            new X509Purpose([X509Purpose::PURPOSE_SSL_SERVER]),
            new X509HostnamePattern($requiredName),
            new X509KeyPair(),
        ]));
    }
}
