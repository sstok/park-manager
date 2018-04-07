<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\ApplicationFoundation\Tests\Message;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessage;
use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ServiceMessagesTest extends TestCase
{
    /** @test */
    public function its_constructable()
    {
        $messages = new ServiceMessages();

        self::assertCount(0, $messages);
        self::assertEquals([], $messages->all());
        self::assertEquals([], $messages->allOf('error'));
        self::assertFalse($messages->hasErrors());
    }

    /** @test */
    public function it_supports_adding_messages()
    {
        $messages = new ServiceMessages();
        $messages->add($message1 = ServiceMessage::error('Whoops'));
        $messages->add($message2 = ServiceMessage::error('Whoops again'));
        $messages->add($message3 = ServiceMessage::notice('Whoops again'));

        self::assertCount(3, $messages);
        self::assertEquals(['error' => [$message1, $message2], 'notice' => [$message3]], $messages->all());
        self::assertEquals([$message1, $message2], $messages->allOf('error'));
        self::assertEquals([$message3], $messages->allOf('notice'));
        self::assertTrue($messages->hasErrors());
    }

    /** @test */
    public function it_merges_messages_from_another_list()
    {
        $messages = new ServiceMessages();
        $messages->add($message1 = ServiceMessage::error('Whoops'));
        $messages->add($message2 = ServiceMessage::error('Whoops again'));
        $messages->add($message3 = ServiceMessage::notice('Whoops again'));

        $messages2 = new ServiceMessages();
        $messages2->add($message4 = ServiceMessage::error('Whoops2'));
        $messages2->add($message5 = ServiceMessage::warning('Whoops again2'));
        $messages2->add($message6 = ServiceMessage::notice('Whoops again2'));
        $messages->merge($messages2);

        self::assertCount(6, $messages);
        self::assertEquals(
            [
                'error' => [$message1, $message2, $message4],
                'notice' => [$message3, $message6],
                'warning' => [$message5],
            ],
            $messages->all()
        );
    }

    /** @test */
    public function it_clears_messages()
    {
        $messages = new ServiceMessages();
        $messages->add($message1 = ServiceMessage::error('Whoops'));
        $messages->add($message2 = ServiceMessage::error('Whoops again'));
        $messages->add($message3 = ServiceMessage::notice('Whoops again'));
        $messages->clear();

        self::assertCount(0, $messages);
        self::assertEquals([], $messages->all());
        self::assertFalse($messages->hasErrors());
    }
}
