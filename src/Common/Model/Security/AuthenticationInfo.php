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

namespace ParkManager\Common\Model\Security;

/**
 * An AuthenticationInfo ValueObject holds the Authentication
 * Information of a User.
 *
 * The implementation can be a username/password combination,
 * a One Time Password token, Public Key. Or anything which can
 * be used to authenticate the user.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface AuthenticationInfo
{
    /**
     * Reconstruct the ValueObject from an array.
     *
     * @param array $information
     *
     * @return self
     */
    public static function fromArray(array $information);

    /**
     * Convert this object to an array structure.
     *
     * Note: Don't simple cast $this to an array, make keys explicit!
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether authentication is different/has changed.
     *
     * @param AuthenticationInfo $authentication
     *
     * @return bool
     */
    public function equals(AuthenticationInfo $authentication): bool;
}
