<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Webhosting\Constraint;

use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\DBConstraints;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use ParkManager\Tests\SpaceConstraintsEquals;
use ParkManager\UI\Web\Form\Type\Webhosting\Constraint\WebhostingConstraintsType;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @internal
 */
final class WebhostingConstraintsTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    /** @test */
    public function it_has_initial_data_when_empty(): void
    {
        $form = $this->factory->create(WebhostingConstraintsType::class);

        self::assertEquals(new Constraints(), $form->getData());
    }

    /** @test */
    public function it_produces_same_config_when_unchanged(): void
    {
        $form = $this->factory->create(WebhostingConstraintsType::class, $constraints = new Constraints());

        $form->submit([], false);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
        self::assertSame($constraints, $form->getData());
    }

    /** @test */
    public function it_handles_empty_data_submit(): void
    {
        $form = $this->factory->create(WebhostingConstraintsType::class, new Constraints());
        $form->submit([]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        $form = $this->factory->create(WebhostingConstraintsType::class);
        $form->submit([]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }

    /**
     * @test
     * @dataProvider provideNewConfigs
     */
    public function it_produces_new_config_when_changed(array $submit, Constraints $expectedConstraints): void
    {
        $form = $this->factory->create(WebhostingConstraintsType::class, new Constraints());

        $orig = [
            'storageSize' => [
                'isInf' => '1',
                'value' => '10.00',
                'unit' => 'byte',
            ],
            'monthlyTraffic' => -1,
            'email' => [
                'maxStorageSize' => [
                    'isInf' => '1',
                    'value' => '10.00',
                    'unit' => 'byte',
                ],
                'spamFilterCount' => -1,
                'maximumAddressCount' => 0,
                'maximumForwardCount' => -1,
                'maximumMailboxCount' => -1,
                'mailListCount' => 0,
            ],
            'database' => [
                'providedStorageSize' => -1,
                'maximumAmountPerType' => -1,
                'enabledPgsql' => true,
                'enabledMysql' => true,
            ],
        ];

        $merged = \array_merge($orig, $submit);
        $merged['email'] = \array_merge($orig['email'], $submit['email'] ?? []);
        $merged['database'] = \array_merge($orig['database'], $submit['database'] ?? []);
        $form->submit($merged);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
        self::assertThat($form->getData(), new SpaceConstraintsEquals($expectedConstraints));
    }

    public function provideNewConfigs(): iterable
    {
        $defaultConstraint = new Constraints();

        yield [
            'storageSize' => [
                'storageSize' => [
                    'value' => '10.00',
                    'unit' => 'gib',
                ],
            ],
            $defaultConstraint->setStorageSize(new ByteSize(10.00, 'gib')),
        ];

        yield 'monthlyTraffic' => [
            [
                'monthlyTraffic' => 10,
            ],
            $defaultConstraint->setMonthlyTraffic(10),
        ];

        yield 'email' => [
            [
                'email' => [
                    'spamFilterCount' => -1,
                    'maximumAddressCount' => 20,
                    'maximumForwardCount' => -1,
                    'maximumMailboxCount' => -1,
                    'mailListCount' => 0,
                ],
            ],
            $defaultConstraint->setEmail(
                new EmailConstraints([
                    'maximumAddressCount' => 20,
                ])
            ),
        ];

        yield 'database' => [
            [
                'database' => [
                    'providedStorageSize' => -1,
                    'maximumAmountPerType' => -1,
                    'enabledPgsql' => true,
                    'enabledMysql' => false,
                ],
            ],
            $defaultConstraint->setDatabase(
                new DBConstraints([
                    'enabledMysql' => false,
                ])
            ),
        ];
    }
}
