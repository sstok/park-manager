<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\Translation\TranslatableMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

/**
 * @internal
 */
final class TranslatableMessageTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $message = new TranslatableMessage('test.message');
        self::assertSame('test.message', $message->getMessage());
        self::assertSame([], $message->getParameters());
        self::assertNull($message->getDomain());

        $message = new TranslatableMessage('test.message', ['count' => 5]);
        self::assertSame('test.message', $message->getMessage());
        self::assertSame(['count' => 5], $message->getParameters());
        self::assertNull($message->getDomain());

        $message = new TranslatableMessage('test.message', ['count' => 5], domain: 'navigation');
        self::assertSame('test.message', $message->getMessage());
        self::assertSame(['count' => 5], $message->getParameters());
        self::assertSame('navigation', $message->getDomain());
    }

    /**
     * @dataProvider provideIt_translatesCases
     *
     * @test
     */
    public function it_translates(string $expected, TranslatableMessage $translatable, string $translation, string $locale): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource(
            'array',
            [$translatable->getMessage() => $translation],
            $locale,
            $translatable->getDomain()
        );
        $translator->addResource(
            'array',
            ['what' => 'super'],
            'de',
            $translatable->getDomain()
        );
        $translator->addResource(
            'array',
            ['what' => 'super toll'],
            'de',
            'questions'
        );

        self::assertSame($expected, $translatable->trans($translator, $locale));
    }

    /**
     * @return \Generator<int, array{0: string, 1: TranslatableMessage, 2: string, 3: string}>
     */
    public static function provideIt_translatesCases(): iterable
    {
        yield ['Symfony est super !', new TranslatableMessage('Symfony is great!', [], ''), 'Symfony est super !', 'fr'];
        yield ['Symfony est awesome !', new TranslatableMessage('Symfony is %what%!', ['%what%' => 'awesome'], ''), 'Symfony est %what% !', 'fr'];
    }

    /**
     * @dataProvider provideFlattened_transCases
     *
     * @test
     *
     * @param array<string, array<string, mixed>> $messages
     */
    public function flattened_trans(string $expected, array $messages, TranslatableMessage $translatable): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', $messages, 'fr', '');

        self::assertSame($expected, $translatable->trans($translator, 'fr'));
    }

    /**
     * @return \Generator<int, array{0: string, 1: array<string, mixed>, 2: TranslatableMessage}>
     */
    public static function provideFlattened_transCases(): iterable
    {
        $messages = [
            'symfony' => [
                'is' => [
                    'great' => 'Symfony est super!',
                ],
            ],
            'foo' => [
                'bar' => [
                    'baz' => 'Foo Bar Baz',
                ],
                'baz' => 'Foo Baz',
            ],
        ];

        yield ['Symfony est super!', $messages, new TranslatableMessage('symfony.is.great', [], '')];
        yield ['Foo Bar Baz', $messages, new TranslatableMessage('foo.bar.baz', [], '')];
        yield ['Foo Baz', $messages, new TranslatableMessage('foo.baz', [], '')];
    }

    /** @test */
    public function to_string(): void
    {
        self::assertSame('Symfony is great!', (string) new TranslatableMessage('Symfony is great!'));
    }
}
