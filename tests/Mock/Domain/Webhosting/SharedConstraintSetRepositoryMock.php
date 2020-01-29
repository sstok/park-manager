<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintSetNotFound;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSetRepository;
use ParkManager\Tests\Mock\Domain\MockRepository;

/** @internal */
final class SharedConstraintSetRepositoryMock implements SharedConstraintSetRepository
{
    /** @use MockRepository<SharedConstraintSet> */
    use MockRepository;

    /**
     * @inheritDoc
     */
    public function get(ConstraintSetId $id): SharedConstraintSet
    {
        return $this->mockDoGetById($id);
    }

    public function save(SharedConstraintSet $constraintSet): void
    {
        $this->mockDoSave($constraintSet);
    }

    public function remove(SharedConstraintSet $constraintSet): void
    {
        $this->mockDoRemove($constraintSet);
    }

    protected function throwOnNotFound($key): void
    {
        throw new ConstraintSetNotFound($key);
    }
}
