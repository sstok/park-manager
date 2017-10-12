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

use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccount;

/**
 * A ConfigurationApplier applies the Capability's configuration
 * on the given webhosting account.
 *
 * This sub-system should only be used when the limitation applies
 * outside of the webhosting system (like a filesystem quota).
 *
 * When applying is not possible (for any reason) a message
 * should be "logged" to inform the UI layer about this failure.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface ConfigurationApplier
{
    public function apply(Capability $configuration, WebhostingAccount $account, LogMessages $messages): void;
}
