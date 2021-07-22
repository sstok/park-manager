<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Model;

final class CommandDto
{
    public function __construct(
        /**
         * The model-data originally passed to the form.
         */
        public mixed $model = null,

        /**
         * All forms fields (root level).
         *
         * @var array<string, mixed>
         */
        public array $fields = [],

        /**
         * Forms fields that have changed (root level), with their new value.
         *
         * @var array<string, mixed>
         */
        public array $changes = []
    ) {
    }
}
