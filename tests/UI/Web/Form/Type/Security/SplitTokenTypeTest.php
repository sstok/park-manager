<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Security;

use ParkManager\UI\Web\Form\Type\Security\SplitTokenType;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TransformationFailureExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @internal
 */
final class SplitTokenTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    private FakeSplitTokenFactory $splitTokenFactory;

    protected function setUp(): void
    {
        $this->splitTokenFactory = new FakeSplitTokenFactory();

        parent::setUp();
    }

    /**
     * @return FormTypeInterface[]
     */
    protected function getTypes(): array
    {
        return [
            new SplitTokenType($this->splitTokenFactory),
        ];
    }

    /**
     * @return FormTypeExtensionInterface[]
     */
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

        self::assertNull($form->getData());
        self::assertSame('', $form->getViewData());
        self::assertFormIsValid($form);
    }

    private static function assertFormIsValid(FormInterface $form): void
    {
        self::assertNull($form->getTransformationFailure());
        self::assertCount(0, $form->getErrors());
    }

    /** @test */
    public function it_works_with_model_data_as_string(): void
    {
        $form = $this->factory->create(SplitTokenType::class, FakeSplitTokenFactory::FULL_TOKEN);

        self::assertEquals($this->splitTokenFactory->fromString(FakeSplitTokenFactory::FULL_TOKEN), $form->getData());
        self::assertSame(FakeSplitTokenFactory::FULL_TOKEN, $form->getViewData());
        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_works_with_model_data_as_split_token(): void
    {
        $form = $this->factory->create(SplitTokenType::class, $token = $this->splitTokenFactory->generate());

        self::assertEquals($token, $form->getData());
        self::assertSame(FakeSplitTokenFactory::FULL_TOKEN, $form->getViewData());
        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_handles_an_invalid_token_submission(): void
    {
        $form = $this->factory->create(SplitTokenType::class, $token = $this->splitTokenFactory->generate());

        $form->submit('Nope');

        self::assertNull($form->getData());
        self::assertSame('Nope', $form->getViewData());

        self::assertNotNull($form->getTransformationFailure());
        self::assertStringEndsWith('Invalid SplitToken provided.', $form->getTransformationFailure()->getMessage());
    }

    /** @test */
    public function it_handles_an_empty_token(): void
    {
        $form = $this->factory->create(SplitTokenType::class, $token = $this->splitTokenFactory->generate());

        $form->submit('');

        self::assertNull($form->getData());
        self::assertSame('', $form->getViewData());
        self::assertFormIsValid($form);
    }

    /** @test */
    public function it_handles_an_invalid_token_type(): void
    {
        $form = $this->factory->create(SplitTokenType::class);

        $form->submit('1');

        self::assertNull($form->getData());
        self::assertSame('1', $form->getViewData());

        self::assertNotNull($form->getTransformationFailure());
        self::assertStringEndsWith('Invalid SplitToken provided.', $form->getTransformationFailure()->getMessage());
    }

    /** @test */
    public function it_handles_invalid_model_data(): void
    {
        $form = $this->factory->create(SplitTokenType::class, 'Nope');

        self::assertSame('Nope', $form->getData());
        self::assertSame('', $form->getViewData());

        self::assertNull($form->getTransformationFailure());

        $errors = $form->getErrors();

        self::assertCount(1, $errors);
        self::assertSame('invalid_split_token', $errors->current()->getMessage());
    }

    /** @test */
    public function it_handles_invalid_model_data_at_transformer(): void
    {
        $this->expectExceptionObject(new TransformationFailedException('Expected a SplitToken object.'));

        $this->factory->create(SplitTokenType::class, 9349842);
    }
}
