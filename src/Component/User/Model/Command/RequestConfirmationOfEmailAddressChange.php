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

namespace ParkManager\Component\User\Model\Command;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RequestConfirmationOfEmailAddressChange extends Command
{
    use PayloadTrait;
    use UserIdTrait;

    public function __construct(string $id, string $email, string $canonicalEmail)
    {
        $this->init();
        $this->setPayload(['id' => $id, 'email' => $email, 'canonical_email' => $canonicalEmail]);
    }

    public function email(): string
    {
        return $this->payload['email'];
    }

    public function canonicalEmail(): string
    {
        return $this->payload['canonical_email'];
    }
}
