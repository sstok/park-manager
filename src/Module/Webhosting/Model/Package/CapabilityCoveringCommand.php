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

namespace ParkManager\Module\Webhosting\Model\Package;

use ParkManager\Module\Webhosting\Model\Account\AccountIdAwareCommand;

/**
 * The CapabilityCoveringCommand interface must be implemented by
 * Webhosting commands that could trigger a capability restriction.
 *
 * Note that this only applies to operations that either create a new entity,
 * or modify an existing one. A delete operation should not trigger a capability
 * restriction.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface CapabilityCoveringCommand extends AccountIdAwareCommand
{
    /**
     * Returns the Capability name this command affects.
     *
     * By convention each operation must only do one thing (and one thing only),
     * _you don't register an FTP user and a mailbox during the same process._
     *
     * @return string The FQCN of the Capability this command affects
     */
    public static function getCapability(): string;
}
