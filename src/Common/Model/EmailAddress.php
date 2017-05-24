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

namespace ParkManager\Common\Model;

use Assert\Assertion;

/**
 * EmailAddress ValueObject.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class EmailAddress
{
    private $name;
    private $host;
    private $address;

    public function __construct(string $address)
    {
        Assertion::email($address);

        $atPosition = mb_strpos($address, '@');
        $this->name = mb_substr($address, 0, $atPosition);
        $this->host = mb_substr($address, $atPosition + 1);
        $this->address = $address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function __toString(): string
    {
        return $this->address;
    }

    public function toString(): string
    {
        return $this->address;
    }
}
