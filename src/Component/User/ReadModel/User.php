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

namespace ParkManager\Component\User\ReadModel;

use Doctrine\Common\Collections\Collection;
use ParkManager\Component\Security\Token\SplitTokenValueHolder;
use ParkManager\Component\User\Model\UserId;

/**
 * READ-ONLY Representation of a user.
 */
class User
{
    /**
     * @var UserId
     */
    public $id;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $canonicalEmail;

    /**
     * @var string|null
     */
    public $password;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var Collection
     */
    public $roles;

    /**
     * @var SplitTokenValueHolder|null
     */
    public $passwordResetToken;

    public function __construct(UserId $id)
    {
        $this->id = $id;
    }
}
