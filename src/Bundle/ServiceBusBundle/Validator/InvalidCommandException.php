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

namespace ParkManager\Bundle\ServiceBusBundle\Validator;

use League\Tactician\Exception\Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class InvalidCommandException extends \Exception implements Exception
{
    private $command;
    private $violations;

    public static function onCommand(object $command, ConstraintViolationListInterface $violations): self
    {
        $exception = new static(
            'Validation failed for '.\get_class($command).
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
