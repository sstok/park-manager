<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Form\Type\Security;

use ParkManager\Bundle\CoreBundle\Form\Type\Security\SplitTokenType;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Symfony\Component\Form\Extension\Core\Type\TransformationFailureExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * @internal
 */
final class SplitTokenTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    /** @var FakeSplitTokenFactory */
    private $splitTokenFactory;

    protected function setUp(): void
    {
        $this->splitTokenFactory = new FakeSplitTokenFactory();

        parent::setUp();
    }

    protected function getTypes(): array
    {
        return [
            new SplitTokenType($this->splitTokenFactory, new IdentityTranslator()),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new TransformationFailureExtension(),
        ];
    }

    /** @test */
    public function it_works_with_empty_model_data(): void
    {
        $form = $this->factory->create(SplitTokenType::class);

        static::assertNull($form->getData());
        static::assertEquals('', $form->getViewData());
        self::assertFormIsValid($form);
    }

    private static function assertFormIsValid(FormInterface $form): void
    {
        static::assertNull($form->getTransformationFailure());
        static::assertCount(0, $form->getErrors());
    }

    /** @test */
    public function it_works_with_model_data_as_string(): void
    {
        $form = $this->factory->create(SplitTokenType::class, FakeSplitTokenFactory::FULL_TOKEN);

        static::assertEquals($this->splitTokenFactory->fromString(FakeSplitTokenFactory::FULL_TOKEN), $form->getData());
        static::assertEquals(FakeSplitTokenFactory::FULL_TOKEN, $form->getViewData());
        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_works_with_model_data_as_Split_token(): void
    {
        $form = $this->factory->create(SplitTokenType::class, $token = $this->splitTokenFactory->generate());

        static::assertEquals($token, $form->getData());
        static::assertEquals(FakeSplitTokenFactory::FULL_TOKEN, $form->getViewData());
        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_handles_an_invalid_token(): void
    {
        $form = $this->factory->create(SplitTokenType::class, $token = $this->splitTokenFactory->generate());

        $form->submit('Nope');

        static::assertNull($form->getData());
        static::assertEquals('Nope', $form->getViewData());

        static::assertNotNull($form->getTransformationFailure());
        static::assertStringEndsWith('Invalid SplitToken provided.', $form->getTransformationFailure()->getMessage());
    }

    /** @test */
    public function it_handles_an_empty_token(): void
    {
        $form = $this->factory->create(SplitTokenType::class, $token = $this->splitTokenFactory->generate());

        $form->submit('');

        static::assertNull($form->getData());
        static::assertEquals('', $form->getViewData());
        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_handles_an_invalid_token_type(): void
    {
        $form = $this->factory->create(SplitTokenType::class);

        $form->submit(1);

        static::assertNull($form->getData());
        static::assertEquals(1, $form->getViewData());

        static::assertNotNull($form->getTransformationFailure());
        static::assertStringEndsWith('Invalid SplitToken provided.', $form->getTransformationFailure()->getMessage());
    }

    /** @test */
    public function it_handles_invalid_model_data(): void
    {
        $form = $this->factory->create(SplitTokenType::class, 'Nope');

        static::assertEquals('Nope', $form->getData());
        static::assertEquals('', $form->getViewData());

        static::assertNull($form->getTransformationFailure());

        $errors = $form->getErrors();

        static::assertCount(1, $errors);
        static::assertEquals('invalid_split_token', $errors->current()->getMessage());
    }
}
