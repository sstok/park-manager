<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\Core\Tests\Model\Administrator\Event;

use ParkManager\Component\Core\Model\Administrator\AdministratorId;
use ParkManager\Component\Core\Model\Administrator\Event\AdministratorNameWasChanged;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AdministratorNameWasChangedTest extends TestCase
{
    private const USER_ID = '45a8ce38-5405-11e7-8853-acbc32b58315';

    /** @test */
    public function its_constructable()
    {
        $command = new AdministratorNameWasChanged($id = AdministratorId::fromString(self::USER_ID), 'First', 'Named');

        self::assertEquals(AdministratorId::fromString(self::USER_ID), $command->id());
        self::assertTrue($id->equals($command->id()));
        self::assertEquals('Named', $command->lastName());
        self::assertEquals('First', $command->firstName());
    }
}
