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
use function get_class;
use function sprintf;

final class CommandFailedValidation extends \Exception implements Exception
{
    private $command;
    private $violations;

    public static function onCommand(object $command, ConstraintViolationListInterface $violations): self
    {
        $exception = new static(
            sprintf('Validation failed for %s with %d violation(s).', get_class($command), $violations->count())
        );

        $exception->command    = $command;
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
