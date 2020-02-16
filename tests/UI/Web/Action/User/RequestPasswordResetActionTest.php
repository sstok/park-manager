<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Action\User;

use ParkManager\Tests\WebTranslatedAssertionTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class RequestPasswordResetActionTest extends WebTestCase
{
    use WebTranslatedAssertionTrait;

    /** @test */
    public function it_requests_a_password_reset(): void
    {
        $client = static::createClient([], ['HTTPS' => true]);

        $crawler = $client->request('GET', '/password-reset');
        $form = $crawler->selectButton('submit')->form();
        $form['request_user_password_reset[email]'] = 'jane@example.com';

        $client->submit($form);

        self::assertEmailCount(1);
        $email = self::getMailerMessage(0);

        self::assertEmailHeaderSame($email, 'To', 'jane@example.com');
        self::assertEmailTextBodyContains($email, 'To reset your password');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertRouteSame('park_manager.user.security_login');
        self::assertSelectorTranslatedTextContains('body div', 'flash.password_reset_send');
    }

    /** @test */
    public function it_requests_a_password_reset_when_one_was_already_requested(): void
    {
        $client = static::createClient([], ['HTTPS' => true]);

        $crawler = $client->request('GET', '/password-reset');
        $form = $crawler->selectButton('submit')->form();
        $form['request_user_password_reset[email]'] = 'ronet@example.com';
        $client->submit($form);

        self::assertEmailCount(0);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertRouteSame('park_manager.user.security_login');
        self::assertSelectorTranslatedTextContains('body div', 'flash.password_reset_send');
    }
}
