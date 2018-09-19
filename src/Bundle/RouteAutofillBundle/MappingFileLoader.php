<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
        $loader          = new self('nope');
        $loader->mapping = $mapping;

        return $loader;
    }

    public function all()
    {
        if ($this->mapping === null) {
            $this->mapping = include $this->filename;
        }

        return $this->mapping;
    }
}
