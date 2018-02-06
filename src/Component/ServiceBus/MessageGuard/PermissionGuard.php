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

namespace ParkManager\Component\ServiceBus\MessageGuard;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface PermissionGuard
{
    /**
     * Permission is granted, the message is allowed.
     */
    public const PERMISSION_ALLOW = 1;

    /**
     * Permission is denied, the message is denied.
     */
    public const PERMISSION_DENY = 0;

    /**
     * Permission is unknown, abstain deciding to allow other PermissionGuard's decide.
     */
    public const PERMISSION_ABSTAIN = -1;

    /**
     * Returns whether the message is allowed to be handled.
     *
     * The PermissionGuard is expected to return one of the following:
     * self::PERMISSION_ALLOW, self::PERMISSION_DENY
     * or self::PERMISSION_ABSTAIN
     *
     * @param object $message
     *
     * @return int
     */
    public function decide(object $message): int;
}
