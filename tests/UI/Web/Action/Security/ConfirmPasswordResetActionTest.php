<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Action\Security;

use ParkManager\Infrastructure\Mailer\TemplatedEmail;
use ParkManager\Tests\WebTranslatedAssertionTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

        $client->request('GET', $email->getOriginalContext()['url']);
        self::assertResponseIsSuccessful();

        $client->submitForm('submit', [
            'confirm_user_password_reset[password][password][first]' => 'H@ll0Wrld!ItsMo1#',
            'confirm_user_password_reset[password][password][second]' => 'H@ll0Wrld!ItsMo1#',
        ]);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertRouteSame('park_manager.security_login');
        self::assertSelectorTranslatedTextContains('body div', 'flash.password_reset_accepted');
    }

    /** @test */
    public function it_fails_with_an_invalid_token(): void
    {
        $client = self::createClient([], ['HTTPS' => true]);

        $client->request('GET', '/password-reset/confirm/FooBangBar0100010101');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertRouteSame('park_manager.security_confirm_password_reset');
        self::assertSelectorTranslatedTextContains('body div', 'password_reset.invalid_token', [], 'validators');
    }
}
