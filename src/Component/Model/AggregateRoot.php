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

namespace ParkManager\Component\Model;

/**
 * AggregateRoot.
 *
 * Note: Constructor should be protected and creation
 * should happen through static factory method(s).
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface AggregateRoot
{
    /**
     * Returns the id of this entity.
     *
     * @return mixed Identity value-object
     */
    public function id();
}
