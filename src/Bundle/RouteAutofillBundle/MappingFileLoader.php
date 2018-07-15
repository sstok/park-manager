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

namespace ParkManager\Bundle\RouteAutofillBundle;

/** @internal */
final class MappingFileLoader
{
    private $filename;
    private $mapping;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public static function fromArray(array $mapping): self
    {
        $loader = new self('nope');
        $loader->mapping = $mapping;

        return $loader;
    }

    public function all()
    {
        if (null === $this->mapping) {
            $this->mapping = include $this->filename;
        }

        return $this->mapping;
    }
}
