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

namespace ParkManager\Bridge\Doctrine\Type;

use ParkManager\Component\SharedKernel\RootEntityOwner;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RootEntityOwnerType extends DomainIdType
{
    public const NAME = 'park_manager_root_entity_owner';
    public const OBJECT_CLASS = RootEntityOwner::class;
}
