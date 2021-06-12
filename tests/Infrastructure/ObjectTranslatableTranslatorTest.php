<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure;

use Carbon\CarbonInterval;
use DateInterval;
use ParkManager\Infrastructure\ObjectTranslatableTranslator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Stringable;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class ObjectTranslatableTranslatorTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_delegates_locale_change(): void
    {
        $translatorProphecy = $this->prophesize(LocalAwareTranslatorMock::class);
        $translatorProphecy->setLocale('en')->shouldBeCalled();
        $realTranslator = $translatorProphecy->reveal();

        $translator = new ObjectTranslatableTranslator($realTranslator);
        $translator->setLocale('en');
    }

    /** @test */
    public function it_translates_regular_id(): void
    {
        $stringableObj = new class() implements Stringable {
            public function __toString(): string
            {
                return 'value-string';
            }
        };

        $translatorProphecy = $this->prophesize(TranslatorInterface::class);
        $translatorProphecy->trans('message', ['id' => 1], 'validators', 'en')->willReturn('message 1');
        $translatorProphecy->trans('message', ['id' => 2], 'validators', null)->willReturn('message 2');
        $translatorProphecy->trans('message', ['id' => 3], null, null)->willReturn('message 3');
        $translatorProphecy->trans('message', ['id' => 'value-string'], null, null)->willReturn('message 4');
        $translatorProphecy->trans('message2', [], null, null)->willReturn('message 5');
        $realTranslator = $translatorProphecy->reveal();

        $translator = new ObjectTranslatableTranslator($realTranslator);

        self::assertSame('message 1', $translator->trans('message', ['id' => 1], 'validators', 'en'));
        self::assertSame('message 2', $translator->trans('message', ['id' => 2], 'validators'));
        self::assertSame('message 3', $translator->trans('message', ['id' => 3]));
        self::assertSame('message 4', $translator->trans('message', ['id' => $stringableObj]));
        self::assertSame('message 5', $translator->trans('message2'));
    }

    /** @test */
    public function it_translates_translatable_value(): void
    {
        $translatorProphecy = $this->prophesize(TranslatorInterface::class);
        $translatorProphecy->trans('label.storage_size', ['id' => 'value1'], null, null)->willReturn('message 1');
        $translatorProphecy->trans('label.storage_size', ['id' => 'value2'], null, 'en')->willReturn('message 2');
        $realTranslator = $translatorProphecy->reveal();

        $translator = new ObjectTranslatableTranslator($realTranslator);

        $translatableValue1 = new class() implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, string $locale = null): string
            {
                Assert::assertInstanceOf(ObjectTranslatableTranslator::class, $translator);
                Assert::assertNull($locale);

                return 'value1';
            }
        };

        $translatableValue2 = new class() implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, string $locale = null): string
            {
                Assert::assertInstanceOf(ObjectTranslatableTranslator::class, $translator);
                Assert::assertSame('en', $locale);

                return 'value2';
            }
        };

        self::assertSame('message 1', $translator->trans('label.storage_size', ['id' => $translatableValue1]));
        self::assertSame('message 2', $translator->trans('label.storage_size', ['id' => $translatableValue2], null, 'en'));
    }

    /** @test */
    public function it_translates_date_interval(): void
    {
        $translatorProphecy = $this->prophesize(LocalAwareTranslatorMock::class);
        $translatorProphecy->trans('label.period', ['period' => '1 week 2 dagen'], null, null)->willReturn('translated (nl)');
        $translatorProphecy->trans('label.period', ['period' => '1 week 2 days'], null, 'en')->willReturn('translated (en)');
        $translatorProphecy->getLocale()->willReturn('nl_nl');
        $realTranslator = $translatorProphecy->reveal();

        $dataInterval1 = new DateInterval('P1W2D');
        $dataInterval2 = CarbonInterval::fromString('P1W2D')->locale('en');

        $translator = new ObjectTranslatableTranslator($realTranslator);

        // With explicit local
        self::assertSame('translated (en)', $translator->trans('label.period', ['period' => $dataInterval1], null, 'en'));
        self::assertSame('translated (en)', $translator->trans('label.period', ['period' => $dataInterval2], null, 'en'));

        // With locale from wrapped translator
        self::assertSame('translated (nl)', $translator->trans('label.period', ['period' => $dataInterval1]));
        self::assertSame('translated (nl)', $translator->trans('label.period', ['period' => $dataInterval2]));

        self::assertSame('en', $dataInterval2->locale(), 'Locale of CarbonInterval should not be changed');
    }
}

interface LocalAwareTranslatorMock extends TranslatorInterface, LocaleAwareInterface
{
}
