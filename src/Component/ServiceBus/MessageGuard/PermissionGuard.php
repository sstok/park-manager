<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Component\ServiceBus\MessageGuard;

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
