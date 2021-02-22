<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\UI\Web\Form\Type\DomainNamePairType;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @internal
 */
final class DomainNamePairTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    /** @test */
    public function it_accepts_null_as_value(): void
    {
        $form = $this->factory->create(DomainNamePairType::class);

        self::assertNull($form->getData());

        $form->submit([]);

        self::assertTrue($form->isValid());
        self::assertNull($form->getData());
    }

    /** @test */
    public function it_only_produces_when_both_fields_are_not_empty(): void
    {
        $form = $this->factory->create(DomainNamePairType::class);
        $form->submit(['name' => 'hey-now']);

        self::assertTrue($form->isValid());
        self::assertNull($form->getData());

        $form = $this->factory->create(DomainNamePairType::class);
        $form->submit(['tld' => 'now']);

        self::assertTrue($form->isValid());
        self::assertNull($form->getData());
    }

    /** @test */
    public function it_produces_a_domain_name_pair_as_model_data(): void
    {
        $form = $this->factory->create(DomainNamePairType::class, new DomainNamePair('hey-now', 'com'));

        self::assertEquals(new DomainNamePair('hey-now', 'com'), $form->getData());
    }

    /** @test */
    public function it_produces_a_domain_name_pair_as_model_data_on_submit(): void
    {
        $form = $this->factory->create(DomainNamePairType::class);
        $form->submit(['name' => 'hey-now', 'suffix' => 'com']);

        self::assertTrue($form->isValid());
        self::assertEquals(new DomainNamePair('hey-now', 'com'), $form->getData());
    }
}
