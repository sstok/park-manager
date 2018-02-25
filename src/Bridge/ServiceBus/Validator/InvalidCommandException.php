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

namespace ParkManager\Bridge\ServiceBus\Validator;

use League\Tactician\Exception\Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class InvalidCommandException extends \Exception implements Exception
{
    private $command;
    private $violations;

    public static function onCommand(object $command, ConstraintViolationListInterface $violations): self
    {
        $exception = new static(
            'Validation failed for '.get_class($command).
            ' with '.$violations->count().' violation(s).'
        );

        $exception->command = $command;
        $exception->violations = $violations;

        return $exception;
    }

    public function getCommand(): object
    {
        return $this->command;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
