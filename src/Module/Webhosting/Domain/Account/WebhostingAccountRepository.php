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

namespace ParkManager\Module\Webhosting\Domain\Account;

use ParkManager\Module\Webhosting\Domain\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Module\Webhosting\Domain\Account\Exception\WebhostingAccountNotFound;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface WebhostingAccountRepository
{
    /**
     * Returns the model class-name to use when constructing.
     *
     * @return string
     */
    public function getModelClass(): string;

    /**
     * Get User by id.
     *
     * @throws WebhostingAccountNotFound when no account was found with the id
     */
    public function get(WebhostingAccountId $id): WebhostingAccount;

    /**
     * Save the WebhostingAccount in the repository.
     *
     * This will either store a new account or update an existing one.
     */
    public function save(WebhostingAccount $account): void;

    /**
     * Remove an webhosting account registration from the repository.
     *
     * @throws CannotRemoveActiveWebhostingAccount when account is still active
     */
    public function remove(WebhostingAccount $account): void;
}
