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

use Prooph\ServiceBus\QueryBus;
use React\Promise\RejectedPromise;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class QueryResponseNegotiator
{
    /**
     * @param QueryBus $queryBus
     * @param mixed    $query
     *
     * @return mixed
     */
    public static function handle(QueryBus $queryBus, $query)
    {
        $finalResult = null;
        $promise = $queryBus->dispatch($query);

        if ($promise instanceof RejectedPromise) {
            $promise->done();
        }

        $promise->then(
            function ($result) use (&$finalResult) {
                $finalResult = $result;
            }
        );

        return $finalResult;
    }
}
