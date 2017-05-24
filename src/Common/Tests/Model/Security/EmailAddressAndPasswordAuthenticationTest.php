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

namespace ParkManager\Common\Tests\Model\Security;

use ParkManager\Common\Model\EmailAddress;
use ParkManager\Common\Model\Security\AuthenticationInfo;
use ParkManager\Common\Model\Security\EmailAddressAndPasswordAuthentication;
use PHPUnit\Framework\TestCase;

final class EmailAddressAndPasswordAuthenticationTest extends TestCase
{
    /** @test */
    public function its_an_authentication_vo()
    {
        $model = new EmailAddressAndPasswordAuthentication(new EmailAddress('user@example.com'), 'password');
        self::assertInstanceOf(AuthenticationInfo::class, $model);
    }

    /** @test */
    public function it_has_an_EmailAddress()
    {
        $model = new EmailAddressAndPasswordAuthentication($email = new EmailAddress('user@example.com'), 'password');

        self::assertEquals($email, $model->emailAddress());
    }

    /** @test */
    public function it_has_a_password()
    {
        $model = new EmailAddressAndPasswordAuthentication(new EmailAddress('user@example.com'), 'password');

        self::assertEquals('password', $model->password());
    }

    /** @test */
    public function it_produces_a_new_value_object_for_setter_methods()
    {
        $model = new EmailAddressAndPasswordAuthentication($email = new EmailAddress('user@example.com'), 'password');

        self::assertEquals('password', $model->password());
        self::assertEquals($email, $model->emailAddress());

        $model2 = $model->setEmailAddress($emailNew = new EmailAddress('user2@example.com'));
        self::assertEquals($emailNew, $model2->emailAddress());
        self::assertNotSame($model, $model2);

        $model3 = $model2->setPassword('password2');
        self::assertEquals('password2', $model3->password());
        self::assertNotSame($model2, $model3);
    }

    /** @test */
    public function its_serializable()
    {
        $model = new EmailAddressAndPasswordAuthentication($email = new EmailAddress('user@example.com'), 'password');
        $str = serialize($model);
        $restoredModel = unserialize($str, []);

        self::assertEquals($restoredModel, $model);
    }

    /** @test */
    public function its_equatable()
    {
        $model = new EmailAddressAndPasswordAuthentication(new EmailAddress('user@example.com'), 'password');
        $model2 = new EmailAddressAndPasswordAuthentication(new EmailAddress('user@example.com'), 'password');
        $model3 = new EmailAddressAndPasswordAuthentication(new EmailAddress('user@example.com'), 'password2');
        $model4 = new EmailAddressAndPasswordAuthentication(new EmailAddress('user2@example.com'), 'password');

        self::assertTrue($model->equals($model));
        self::assertTrue($model->equals($model2));

        self::assertFalse($model->equals($this->createMock(AuthenticationInfo::class)));
        self::assertFalse($model->equals($model3));
        self::assertFalse($model->equals($model4));
    }
}
