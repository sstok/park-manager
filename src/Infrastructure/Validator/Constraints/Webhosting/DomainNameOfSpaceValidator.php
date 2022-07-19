<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints\Webhosting;

use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DomainNameOfSpaceValidator extends ConstraintValidator
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private DomainNameRepository $domainNameRepository,
        private SpaceRepository $spaceRepository,
        private RepositoryLocator $repositoryLocator,
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        $this->propertyAccessor = $propertyAccessor ?? new PropertyAccessor();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (! $constraint instanceof DomainNameOfSpace) {
            throw new UnexpectedTypeException($constraint, DomainNameOfSpace::class);
        }

        if (! \is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        $domainNameIdentifier = $this->propertyAccessor->getValue($value, $constraint->domainProperty);

        if ($domainNameIdentifier === null) {
            return;
        }

        $domainName = $this->domainNameRepository->get($domainNameIdentifier);
        $space = $this->getSpace($value, $constraint->spaceProperty);

        if ($space !== $domainName->space) {
            $this->context->buildViolation('not_owned_by_same_space')
                ->atPath($constraint->domainProperty)
                ->setParameter('{ domain_name }', $domainName->toString())
                ->setInvalidValue($value)
                ->addViolation()
            ;
        }
    }

    private function getSpace(object $value, string | PropertyPath $propertyPath): Space
    {
        $propertyPath = (string) $propertyPath;

        // Regular property path. Otherwise we need to locate the repository of the EntityId VO.
        if ($propertyPath[0] !== '@') {
            return $this->spaceRepository->get($this->propertyAccessor->getValue($value, $propertyPath));
        }

        $propertyPath = mb_substr($propertyPath, 1);
        $propertyPath = new PropertyPath($propertyPath);
        $entityId = $this->propertyAccessor->getValue($value, $propertyPath->getElement(0));

        return $this->propertyAccessor->getValue(
            $this->repositoryLocator->getById($entityId)->get($entityId),
            $propertyPath->getElement(1),
        );
    }
}
