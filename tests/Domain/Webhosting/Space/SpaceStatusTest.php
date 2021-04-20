<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Space;

use ParkManager\Domain\Webhosting\Space\Exception\InvalidStatus;
use ParkManager\Domain\Webhosting\Space\SpaceStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SpaceStatusTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidTransitions
     */
    public function it_validates_improper_transitions(SpaceStatus $current, SpaceStatus $new, string $message): void
    {
        $this->expectExceptionObject(new InvalidStatus($message));

        SpaceStatus::validateNewStatus($current, $new);
    }

    public function provideInvalidTransitions(): iterable
    {
        yield [SpaceStatus::get('READY'), SpaceStatus::get('ERROR'), 'Cannot change status when already initialized.'];
        yield [SpaceStatus::get('READY'), SpaceStatus::get('REINITIALIZED'), 'Cannot change status when already initialized.'];
        yield [SpaceStatus::get('READY'), SpaceStatus::get('REGISTERED'), 'Cannot change status when already initialized.'];
        yield [SpaceStatus::get('READY'), SpaceStatus::get('REGISTERED'), 'Cannot change status when already initialized.'];
        yield [SpaceStatus::get('READY'), SpaceStatus::get('GETTING_INITIALIZED'), 'Cannot change status when already initialized.'];

        yield [SpaceStatus::get('REINITIALIZED'), SpaceStatus::get('READY'), 'Cannot increase status with more than one greater value.'];
        yield [SpaceStatus::get('GETTING_INITIALIZED'), SpaceStatus::get('REGISTERED'), 'Cannot change status to a lower value unless new status is Error.'];
        yield [SpaceStatus::get('GETTING_INITIALIZED'), SpaceStatus::get('REINITIALIZED'), 'Cannot change status to a lower value unless new status is Error.'];
        yield [SpaceStatus::get('ERROR'), SpaceStatus::get('GETTING_INITIALIZED'), 'Cannot increase status with more than one greater value.'];
        yield [SpaceStatus::get('ERROR'), SpaceStatus::get('REGISTERED'), 'Cannot increase status with more than one greater value.'];
        yield [SpaceStatus::get('ERROR'), SpaceStatus::get('READY'), 'Cannot increase status with more than one greater value.'];
    }

    /**
     * @test
     * @dataProvider provideValidTransitions
     * @doesNotPerformAssertions
     */
    public function it_allows_proper_transitions(SpaceStatus $current, SpaceStatus $new): void
    {
        SpaceStatus::validateNewStatus($current, $new);
    }

    public function provideValidTransitions(): iterable
    {
        yield [SpaceStatus::get('REINITIALIZED'), SpaceStatus::get('GETTING_INITIALIZED')];
        yield [SpaceStatus::get('GETTING_INITIALIZED'), SpaceStatus::get('READY')];

        yield [SpaceStatus::get('REGISTERED'), SpaceStatus::get('ERROR')];
        yield [SpaceStatus::get('GETTING_INITIALIZED'), SpaceStatus::get('ERROR')];
        yield [SpaceStatus::get('REINITIALIZED'), SpaceStatus::get('ERROR')];
        yield [SpaceStatus::get('ERROR'), SpaceStatus::get('REINITIALIZED')];
    }
}
