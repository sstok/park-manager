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

namespace ParkManager\Common\Application;

/**
 * Marker interface for Application Service Request
 * that will change the state of the Domain model(s).
 *
 * See {@see \ParkManager\Common\Application\Request}
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface MutationRequest extends Request
{
}
