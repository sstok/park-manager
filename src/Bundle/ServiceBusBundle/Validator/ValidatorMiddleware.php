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

use League\Tactician\Middleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function count;

final class ValidatorMiddleware implements Middleware
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param object $command
     *
     * @throws CommandFailedValidation
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $constraintViolations = $this->validator->validate($command);

        if (count($constraintViolations) > 0) {
            throw CommandFailedValidation::onCommand($command, $constraintViolations);
        }

        return $next($command);
    }
}
