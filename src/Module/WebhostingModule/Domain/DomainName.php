<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain;

final class DomainName
{
    private $name;
    private $tld;

    public function __construct(string $name, string $tld)
    {
        $this->name = $name;
        $this->tld  = $tld;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function tld(): string
    {
        return $this->tld;
    }

    public function toString(): string
    {
        return $this->name . '.' . $this->tld;
    }
}
