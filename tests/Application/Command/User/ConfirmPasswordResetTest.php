<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Application\Command\User\ConfirmPasswordReset;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;

/**
 * @internal
 */
final class ConfirmPasswordResetTest extends TestCase
{
    private const TOKEN_STRING = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPGKs9SVvbZCkMi8ZxiEVMBw68S';

    /** @test */
    public function its_constructable(): void
    {
        $password = 'my-password-I-forgot';
        $token = FakeSplitTokenFactory::instance()->fromString(self::TOKEN_STRING);

        $command = new ConfirmPasswordReset($token, $password);

        self::assertSame($token, $command->token());
        self::assertSame('my-password-I-forgot', $command->password());
    }
}
