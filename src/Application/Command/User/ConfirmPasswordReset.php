<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use Rollerworks\Component\SplitToken\SplitToken;

final class ConfirmPasswordReset
{
    public SplitToken $token;
    public string $password;

    /**
     * @param string $password The password provided in hash-encoded format
     */
    public function __construct(SplitToken $token, string $password)
    {
        $this->token = $token;
        $this->password = $password;
    }
}
