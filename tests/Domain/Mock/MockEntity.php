<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Mock;

final class MockEntity
{
    /** @var MockIdentity */
    private $id;

    /** @var string|null */
    public $name;

    private $lastName;

    public function __construct(string $id = 'fc86687e-0875-11e9-9701-acbc32b58315', string $name = 'Foobar')
    {
        $this->id = MockIdentity::fromString($id);
        $this->lastName = $name;
    }

    public function id(): MockIdentity
    {
        return $this->id;
    }

    public function lastName()
    {
        return $this->lastName;
    }
}
