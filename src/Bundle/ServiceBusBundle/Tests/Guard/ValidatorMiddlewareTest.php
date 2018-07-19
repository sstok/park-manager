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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\Guard;

use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Command\CommandA;
use ParkManager\Bundle\ServiceBusBundle\Validator\InvalidCommandException;
use ParkManager\Bundle\ServiceBusBundle\Validator\ValidatorMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
final class ValidatorMiddlewareTest extends TestCase
{
    /** @test */
    public function it_throws_when_there_are_violations()
    {
        $list = new ConstraintViolationList([$this->createMock(ConstraintViolationInterface::class)]);
        $middleware = new ValidatorMiddleware($this->createValidator($list));
        $command = new CommandA();

        try {
            $middleware->execute($command, function () {
                return true;
            });
        } catch (InvalidCommandException $e) {
            $this->assertEquals($list, $e->getViolations());
            $this->assertEquals($command, $e->getCommand());
        }
    }

    /** @test */
    public function it_executes_when_there_are_no_violations()
    {
        $list = new ConstraintViolationList([]);
        $middleware = new ValidatorMiddleware($this->createValidator($list));

        self::assertEquals('result', $middleware->execute(new CommandA(), function () {
            return 'result';
        }));
    }

    private function createValidator(ConstraintViolationList $list): ValidatorInterface
    {
        $validatorProphecy = $this->prophesize(ValidatorInterface::class);
        $validatorProphecy->validate(Argument::any())->willReturn($list);

        return $validatorProphecy->reveal();
    }
}
