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
 * Marker interface for Application Service Request.
 *
 * A Request provides the Application Service with information
 * (from the outer system) in a normalized format.
 *
 * A Request can either READING (Query) or WRITHING (Command),
 * in practice which type of Request is issued is not important.
 * A MutationRequest however may trigger an transaction script.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface Request
{
}
