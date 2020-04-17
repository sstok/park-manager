<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Action\User;

use ParkManager\Tests\WebTranslatedAssertionTrait;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class ConfirmPasswordResetActionTest extends WebTestCase
{
    use WebTranslatedAssertionTrait;

    /** @test */
    public function it_resets_a_password(): void
    {
        $client = self::createClient([], ['HTTPS' => true]);

        $client->request('GET', '/password-reset');
        $client->submitForm('submit', ['request_user_password_reset[email]' => 'jane@example.com']);
        $email = self::getMailerMessage(0);
        \assert($email instanceof TemplatedEmail);

        $client->request('GET', $email->getContext()['url']);
        $client->submitForm('submit', [
            'confirm_user_password_reset[password][password][first]' => 'H@ll0Wrld!ItsMo1#',
            'confirm_user_password_reset[password][password][second]' => 'H@ll0Wrld!ItsMo1#',
        ]);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertRouteSame('park_manager.user.security_login');
        self::assertSelectorTranslatedTextContains('body div', 'flash.password_reset_accepted');
    }

    /** @test */
    public function it_fails_with_an_invalid_token(): void
    {
        $client = self::createClient([], ['HTTPS' => true]);

        $client->request('GET', '/password-reset/confirm/FooBangBar0100010101');

        self::assertRouteSame('park_manager.user.security_confirm_password_reset');
        self::assertSelectorTranslatedTextContains('body div', 'password_reset.invalid_token', [], 'validators');
    }
}
