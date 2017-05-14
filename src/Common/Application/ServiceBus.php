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
 * A ServiceBus communicates Application Requests to
 * there designated Application Service.
 *
 * For example UpdateProfile will be communicated to the
 * UpdateProfileService.
 *
 * But a ServiceBus can also decorated for transaction handling
 * and/or authorization validation.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface ServiceBus
{
    /**
     * Execute the Request on the Application Service.
     *
     * Optionally a Service may return a response.
     *
     * @param Request $request
     *
     * @return object|null
     */
    public function execute(Request $request);
}
