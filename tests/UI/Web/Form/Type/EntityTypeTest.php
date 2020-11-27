<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use JsonSerializable;
use ParkManager\Domain\UuidTrait;
use ParkManager\Tests\Mock\Domain\MockRepoResultSet;
use ParkManager\UI\Web\Form\ChoiceList\ResultSetChoiceLoader;
use ParkManager\UI\Web\Form\Type\EntityType;
use Serializable;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @internal
 */
final class EntityTypeTest extends TypeTestCase
{
    /** @test */
    public function it_lists_all_choices(): void
    {
        $entity1 = new MockEntity('2b648417-aabe-4b05-84f1-f19231f0d5a6', 'Kay Williams');
        $entity2 = new MockEntity('3c2ebe20-cb93-44ca-a723-ffe51a825d8f', 'Brad Armstrong');
        $entity3 = new MockEntity('c1025d93-5ad4-405d-8f79-9c9e14b078c0', 'Connie Johnson');
        $entity4 = new MockEntity('fd8bd136-2e2a-4b51-bfe4-ec7f5fa43895', 'Carmen Mills');

        $form = $this->factory->create(EntityType::class, null, ['choice_vary' => [], 'choice_label' => 'name', 'resultset' => new MockRepoResultSet([$entity1, $entity2, $entity3, $entity4])]);

        self::assertEquals(
            [
                'choice_2b648417_aabe_4b05_84f1_f19231f0d5a6' => new ChoiceView($entity1, $entity1->id->toString(), $entity1->name),
                'choice_3c2ebe20_cb93_44ca_a723_ffe51a825d8f' => new ChoiceView($entity2, $entity2->id->toString(), $entity2->name),
                'choice_c1025d93_5ad4_405d_8f79_9c9e14b078c0' => new ChoiceView($entity3, $entity3->id->toString(), $entity3->name),
                'choice_fd8bd136_2e2a_4b51_bfe4_ec7f5fa43895' => new ChoiceView($entity4, $entity4->id->toString(), $entity4->name),
            ],
            $form->createView()->vars['choices']
        );
    }

    /** @test */
    public function it_loads_only_selected_choices_by_value(): void
    {
        $entity1 = new MockEntity('2b648417-aabe-4b05-84f1-f19231f0d5a6', 'Kay Williams');
        $entity2 = new MockEntity('3c2ebe20-cb93-44ca-a723-ffe51a825d8f', 'Brad Armstrong');
        $entity3 = new MockEntity('c1025d93-5ad4-405d-8f79-9c9e14b078c0', 'Connie Johnson');
        $entity4 = new MockEntity('fd8bd136-2e2a-4b51-bfe4-ec7f5fa43895', 'Terry Bell');

        // Cannot test this as part of the Type. Most important part is that values collection is reduced.
        $resultSet = new MockRepoResultSet([$entity1, $entity2, $entity3, $entity4]);
        $choiceLoader = new ResultSetChoiceLoader($resultSet);

        self::assertSame([$entity2, $entity4], $choiceLoader->loadChoicesForValues(['3c2ebe20-cb93-44ca-a723-ffe51a825d8f', 'fd8bd136-2e2a-4b51-bfe4-ec7f5fa43895']));
        self::assertSame([], $choiceLoader->loadChoicesForValues(['03802a64-606c-4b2f-96c5-6fb670773515']));
    }
}

/** @internal */
final class MockIdentity implements Serializable, JsonSerializable
{
    use UuidTrait;
}

/** @internal */
final class MockEntity
{
    public MockIdentity $id;
    public ?string $name = null;

    public function __construct(string $id, string $name = 'Foobar')
    {
        $this->id = MockIdentity::fromString($id);
        $this->name = $name;
    }
}
