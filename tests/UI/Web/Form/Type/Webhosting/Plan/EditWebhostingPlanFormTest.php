<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\UpdatePlan;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Tests\UI\Web\Form\MessageFormTestCase;
use ParkManager\UI\Web\Form\Type\Webhosting\Plan\EditWebhostingPlanForm;
use ParkManager\UI\Web\Form\Type\Webhosting\Plan\WebhostingPlanLabel;

/**
 * @internal
 */
final class EditWebhostingPlanFormTest extends MessageFormTestCase
{
    protected static function getCommandName(): string
    {
        return UpdatePlan::class;
    }

    protected function getTypes(): array
    {
        return [$this->getMessageType(), new WebhostingPlanLabel(['nl', 'de'])];
    }

    /** @test */
    public function it_populates_the_translation_fields(): void
    {
        $plan = new Plan(PlanId::fromString('77dc8b50-42e9-4d95-8a64-7a8208230910'), new Constraints());
        $plan->withLabels(['_default' => 'Super Gold Plan', 'nl' => 'Never Ever Use Dutch!', 'de' => 'Süper Golda Plane']);

        $form = $this->factory->create(EditWebhostingPlanForm::class, $plan);
        $view = $form->createView();

        self::assertArrayHasKey('default_label', $view->children);
        self::assertArrayHasKey('localized_labels', $view->children);

        self::assertEquals(['nl', 'de'], array_keys($view->children['localized_labels']->children));
        self::assertEquals('Super Gold Plan', $view->children['default_label']->vars['value']);

        foreach (['nl' => 'Never Ever Use Dutch!', 'de' => 'Süper Golda Plane'] as $locale => $value) {
            $subView = $view->children['localized_labels']->children[$locale];

            self::assertArrayHasKey('locale', $subView->children);
            self::assertArrayHasKey('value', $subView->children);

            self::assertEquals($locale, $subView->children['locale']->vars['value']);
            self::assertEquals($value, $subView->children['value']->vars['value']);
        }
    }

    /** @test */
    public function it_accepts_an_empty_model(): void
    {
        $form = $this->factory->create(EditWebhostingPlanForm::class);
        $view = $form->createView();

        self::assertArrayHasKey('default_label', $view->children);
        self::assertArrayHasKey('localized_labels', $view->children);

        self::assertEquals([], array_keys($view->children['localized_labels']->children));
        self::assertEquals('', $view->children['default_label']->vars['value']);
    }
}
