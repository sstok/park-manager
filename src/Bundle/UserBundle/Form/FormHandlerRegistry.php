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

namespace ParkManager\Bundle\UserBundle\Form;

use Hostnet\Component\FormHandler\Exception\InvalidHandlerTypeException;
use Hostnet\Component\FormHandler\HandlerRegistryInterface;
use Psr\Container\ContainerInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class FormHandlerRegistry implements HandlerRegistryInterface
{
    private $handlersContainer;

    public function __construct(ContainerInterface $handlersContainer)
    {
        $this->handlersContainer = $handlersContainer;
    }

    public function getType($class)
    {
        if (!$this->handlersContainer->has($class)) {
            throw new InvalidHandlerTypeException($class);
        }

        return $this->handlersContainer->get($class);
    }
}
