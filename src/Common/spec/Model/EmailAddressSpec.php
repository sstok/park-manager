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

namespace spec\ParkManager\Common\Model;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use ParkManager\Common\Model\EmailAddress;
use PhpSpec\ObjectBehavior;

final class EmailAddressSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('admin@example.com');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(EmailAddress::class);
    }

    function it_validates_input()
    {
        $this->beConstructedWith('admin@@example.com');
        $this->shouldThrow(
            new InvalidArgumentException(
                'Value "admin@@example.com" was expected to be a valid e-mail address.',
                Assertion::INVALID_EMAIL,
                null,
                'admin@@example.com'
            )
        )->duringInstantiation();
    }

    function it_holds_a_name()
    {
        $this->getName()->shouldReturn('admin');
    }

    function it_holds_a_host()
    {
        $this->getHost()->shouldReturn('example.com');
    }

    function it_can_be_casted_to_string()
    {
        $this->__toString()->shouldReturn('admin@example.com');
    }
}
