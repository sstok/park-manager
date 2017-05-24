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

namespace ParkManager\Common\Test\Model\Security;

use ParkManager\Common\Model\Security\AuthenticationInfo;

/**
 * The MockAuthentication allows to use a fake Authentication for testing.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class MockAuthentication implements AuthenticationInfo
{
    private $information;

    public function __construct(array $information)
    {
        $this->information = $information;
    }

    public function equals(AuthenticationInfo $authentication): bool
    {
        if (!$authentication instanceof self) {
            return false;
        }

        return $authentication->information === $this->information;
    }

    public function toArray(): array
    {
        return $this->information;
    }

    public static function fromArray(array $information): MockAuthentication
    {
        return new MockAuthentication($information);
    }
}
