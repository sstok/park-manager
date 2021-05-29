<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Space;

use Generator;
use ParkManager\Domain\Webhosting\Space\Exception\InvalidStatus;
use ParkManager\Domain\Webhosting\Space\SpaceSetupStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SpaceSetupStatusTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideInvalidTransitions
     */
    public function it_validates_improper_transitions(SpaceSetupStatus $current, SpaceSetupStatus $new, string $message): void
    {
        $this->expectExceptionObject(new InvalidStatus($message));

        SpaceSetupStatus::validateNewStatus($current, $new);
    }

    /**
     * @return Generator<int, array{0: SpaceSetupStatus, 1: SpaceSetupStatus, 2: string}>
     */
    public function provideInvalidTransitions(): Generator
    {
        yield [SpaceSetupStatus::get('READY'), SpaceSetupStatus::get('ERROR'), 'Cannot change status when already initialized.'];
        yield [SpaceSetupStatus::get('READY'), SpaceSetupStatus::get('REINITIALIZED'), 'Cannot change status when already initialized.'];
        yield [SpaceSetupStatus::get('READY'), SpaceSetupStatus::get('REGISTERED'), 'Cannot change status when already initialized.'];
        yield [SpaceSetupStatus::get('READY'), SpaceSetupStatus::get('REGISTERED'), 'Cannot change status when already initialized.'];
        yield [SpaceSetupStatus::get('READY'), SpaceSetupStatus::get('GETTING_INITIALIZED'), 'Cannot change status when already initialized.'];

        yield [SpaceSetupStatus::get('REINITIALIZED'), SpaceSetupStatus::get('READY'), 'Cannot increase status with more than one greater value.'];
        yield [SpaceSetupStatus::get('GETTING_INITIALIZED'), SpaceSetupStatus::get('REGISTERED'), 'Cannot change status to a lower value unless new status is Error.'];
        yield [SpaceSetupStatus::get('GETTING_INITIALIZED'), SpaceSetupStatus::get('REINITIALIZED'), 'Cannot change status to a lower value unless new status is Error.'];
        yield [SpaceSetupStatus::get('ERROR'), SpaceSetupStatus::get('GETTING_INITIALIZED'), 'Cannot increase status with more than one greater value.'];
        yield [SpaceSetupStatus::get('ERROR'), SpaceSetupStatus::get('REGISTERED'), 'Cannot increase status with more than one greater value.'];
        yield [SpaceSetupStatus::get('ERROR'), SpaceSetupStatus::get('READY'), 'Cannot increase status with more than one greater value.'];
    }

    /**
     * @test
     *
     * @dataProvider provideValidTransitions
     *
     * @doesNotPerformAssertions
     */
    public function it_allows_proper_transitions(SpaceSetupStatus $current, SpaceSetupStatus $new): void
    {
        SpaceSetupStatus::validateNewStatus($current, $new);
    }

    /**
     * @return Generator<int, array{0: SpaceSetupStatus, 1: SpaceSetupStatus}>
     */
    public function provideValidTransitions(): Generator
    {
        yield [SpaceSetupStatus::get('REINITIALIZED'), SpaceSetupStatus::get('GETTING_INITIALIZED')];
        yield [SpaceSetupStatus::get('GETTING_INITIALIZED'), SpaceSetupStatus::get('READY')];

        yield [SpaceSetupStatus::get('REGISTERED'), SpaceSetupStatus::get('ERROR')];
        yield [SpaceSetupStatus::get('GETTING_INITIALIZED'), SpaceSetupStatus::get('ERROR')];
        yield [SpaceSetupStatus::get('REINITIALIZED'), SpaceSetupStatus::get('ERROR')];
        yield [SpaceSetupStatus::get('ERROR'), SpaceSetupStatus::get('REINITIALIZED')];
    }
}
