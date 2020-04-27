<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use Psr\Container\ContainerInterface;

final class ConstraintChecker
{
    /** @var ContainerInterface */
    private $constraintValidators;

    /** @var WebhostingSpaceRepository */
    private $spaceRepository;

    public function __construct(ContainerInterface $constraintValidators, WebhostingSpaceRepository $spaceRepository)
    {
        $this->constraintValidators = $constraintValidators;
        $this->spaceRepository = $spaceRepository;
    }

    /**
     * @throws ConstraintExceeded
     */
    public function validate(SpaceId $spaceId, string $constraintName, array $context = []): void
    {
        $constraints = $this->spaceRepository->get($spaceId)->getConstraints();

        if (! $constraints->has($constraintName)) {
            return;
        }

        $validator = $this->constraintValidators->get($constraintName);
        \assert($validator instanceof ConstraintValidator);
        $validator->validate($spaceId, $constraints->get($constraintName), $context);
    }
}
