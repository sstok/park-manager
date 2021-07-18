<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use Generator;
use ParkManager\Tests\UI\Web\Form\MessageFormTestCase;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @internal
 */
final class ConfirmationFormTest extends MessageFormTestCase
{
    protected static function getCommandName(): string
    {
        return ConfirmCommand::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandHandler = static fn () => null;
    }

    /**
     * @return FormTypeInterface[]
     */
    protected function getTypes(): array
    {
        return [
            $this->getMessageType(),
        ];
    }

    /** @test */
    public function it_sets_view_variables(): void
    {
        $form = $this->factory->create(
            ConfirmationForm::class,
            null,
            [
                'confirmation_title' => 'Do you confirm?',
                'confirmation_message' => 'This cannot be undone, the world will come undone if you continue now!',
                'confirmation_label' => 'DO IT!',
                'cancel_route' => 'ui.stop',
                'command_factory' => static fn () => new ConfirmCommand(),
            ]
        );
        $view = $form->createView()->vars;

        self::assertSame('Do you confirm?', $view['confirmation_title']);
        self::assertSame('This cannot be undone, the world will come undone if you continue now!', $view['confirmation_message']);
        self::assertSame('DO IT!', $view['confirmation_label']);
        self::assertSame(['name' => 'ui.stop', 'arguments' => []], $view['cancel_route']);
        self::assertNull($view['required_value']);

        $form = $this->factory->create(
            ConfirmationForm::class,
            null,
            [
                'confirmation_title' => 'Do you confirm?',
                'confirmation_message' => 'This cannot be undone, the world will come undone if you continue now!',
                'confirmation_label' => 'DO IT!',
                'cancel_route' => ['name' => 'ui.stop', 'arguments' => ['id' => 5]],
                'required_value' => 'oglaZ!',
                'command_factory' => static fn () => new ConfirmCommand(),
            ]
        );
        $view = $form->createView()->vars;

        self::assertSame('Do you confirm?', $view['confirmation_title']);
        self::assertEquals(new TranslatableMessage('This cannot be undone, the world will come undone if you continue now!', ['required_value' => 'oglaZ!']), $view['confirmation_message']);
        self::assertSame('DO IT!', $view['confirmation_label']);
        self::assertSame(['name' => 'ui.stop', 'arguments' => ['id' => 5]], $view['cancel_route']);
        self::assertSame('oglaZ!', $view['required_value']);
    }

    /** @test */
    public function it_confirms(): void
    {
        $form = $this->factory->create(
            ConfirmationForm::class,
            null,
            [
                'confirmation_title' => 'Do you confirm?',
                'confirmation_message' => 'This cannot be undone, the world will come undone if you continue now!',
                'confirmation_label' => 'DO IT!',
                'cancel_route' => 'ui.stop',
                'command_factory' => static fn () => new ConfirmCommand(),
            ]
        );

        $form->submit([]);

        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_confirms_with_required_value(): void
    {
        $form = $this->factory->create(
            ConfirmationForm::class,
            null,
            [
                'confirmation_title' => 'Do you confirm?',
                'confirmation_message' => 'This cannot be undone, the world will come undone if you continue now!',
                'confirmation_label' => 'DO IT!',
                'cancel_route' => 'ui.stop',
                'required_value' => 'oglaZ!',
                'command_factory' => static fn () => new ConfirmCommand(),
            ]
        );

        $form->submit(['required_value' => 'oglaZ!']);

        self::assertFormIsValid($form);
    }

    /**
     * @test
     * @dataProvider provideNonMatchingValues
     */
    public function it_checks_required_value_matches_failure(string $value): void
    {
        $form = $this->factory->create(
            ConfirmationForm::class,
            null,
            [
                'confirmation_title' => 'Do you confirm?',
                'confirmation_message' => 'This cannot be undone, the world will come undone if you continue now!',
                'confirmation_label' => 'DO IT!',
                'cancel_route' => 'ui.stop',
                'required_value' => 'oglaZ!',
                'command_factory' => static fn () => new ConfirmCommand(),
            ]
        );

        $form->submit(['required_value' => $value]);

        $this->assertFormHasErrors(
            $form,
            [
                'required_value' => [
                    new FormError(
                        'value_does_not_match_expected_value',
                        'value_does_not_match_expected_value',
                        [
                            '{{ value }}' => $value,
                            '{{ required_value }}' => 'oglaZ!',
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @return Generator<int, array{0: string}>
     */
    public function provideNonMatchingValues(): Generator
    {
        yield ['oglaZ'];
        yield ['glaZ!'];
        yield ['oglaZ!!'];
        yield ['!oglaZ!'];
        yield ['ogl aZ!'];
    }

    /**
     * @test
     * @dataProvider provideMatchingValues
     */
    public function it_checks_required_value_matches_dispatches(string $value): void
    {
        $form = $this->factory->create(
            ConfirmationForm::class,
            null,
            [
                'confirmation_title' => 'Do you confirm?',
                'confirmation_message' => 'This cannot be undone, the world will come undone if you continue now!',
                'confirmation_label' => 'DO IT!',
                'cancel_route' => 'ui.stop',
                'required_value' => '<ogl aZ!>',
                'command_factory' => static fn () => new ConfirmCommand(),
            ]
        );

        $form->submit(['required_value' => $value]);

        self::assertFormIsValid($form);
    }

    /**
     * @return Generator<int, array{0: string}>
     */
    public function provideMatchingValues(): Generator
    {
        yield ['<ogl aZ!>'];
        yield [' <ogl aZ!> '];
        yield ['<ogl az!>'];
        yield ['<OGL AZ!>'];
    }
}

/**
 * @internal
 */
final class ConfirmCommand
{
}
