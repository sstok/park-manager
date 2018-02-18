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

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Guard;

use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard\PolicyGuard;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\{
    Deeper\MessageC, MessageA, MessageB, ServiceA
};
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 *
 * @todo Test for null value
 */
final class PolicyManagerTest extends TestCase
{
    private $expressionLanguage;
    private $services;

    protected function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();

        $services = $this->prophesize(ContainerInterface::class);
        $services->get('my_service')->willReturn(new ServiceA());
        $this->services = $services->reveal();
    }

    /** @test */
    public function it_abstains_access_when_no_policy_is_registered()
    {
        $manager = new PolicyGuard($this->expressionLanguage, [], [], '{^/$}', [], []);

        self::assertEquals(PolicyGuard::PERMISSION_ABSTAIN, $manager->decide(new \stdClass()));
    }

    /** @test */
    public function it_works_with_expression_for_class()
    {
        $manager = new PolicyGuard(
            $this->expressionLanguage,
            [],
            [
                \stdClass::class => new Expression('my_bar == "bar"'),
                MessageA::class => new Expression('services.get("my_service").getId() == message.id()'),
                MessageB::class => new Expression('services.get("my_service").getId() == message.id() and my_bar == "car"'),
            ],
            '',
            [],
            ['my_bar' => 'bar', 'services' => $this->services]
        );

        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new \stdClass()));
        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageA(1)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageA(2)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageB(1)));
    }

    /** @test */
    public function it_works_with_boolean_for_class()
    {
        $manager = new PolicyGuard(
            $this->expressionLanguage,
            [],
            [
                MessageA::class => true,
                MessageB::class => false,
            ],
            '',
            [],
            []
        );

        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageA(1)));
        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageA(2)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageB(1)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageB(2)));
    }

    /** @test */
    public function it_works_with_expression_for_namespace()
    {
        $manager = new PolicyGuard(
            $this->expressionLanguage,
            [
                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => new Expression('services.get("my_service").getId() == message.id() and my_bar == "bar"'), // Always false; foo=car
            ],
            [
                \stdClass::class => new Expression('my_bar == "car"'),
                MessageB::class => new Expression('services.get("my_service").getId() == message.id()'),
            ],
            '',
            [],
            ['my_bar' => 'car', 'services' => $this->services]
        );

        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new \stdClass()));
        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageB(1)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageB(2)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageA(2)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageA(2)));
    }

    /** @test */
    public function it_works_with_boolean_for_namespace()
    {
        $manager = new PolicyGuard(
            $this->expressionLanguage,
            [
                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => false,
            ],
            [
                \stdClass::class => new Expression('my_bar == "bar"'),
                MessageB::class => new Expression('services.get("my_service").getId() == message.id()'),
            ],
            '',
            [],
            ['my_bar' => 'foo', 'services' => $this->services]
        );

        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageB(1)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageB(2)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageA(1)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageA(2)));
    }

    /** @test */
    public function it_works_with_boolean_for_regexp()
    {
        $manager = new PolicyGuard(
            $this->expressionLanguage,
            [],
            [],
            '{^(?|'.
                'ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\Fixtures\\\(?|MessageA(*:1)|MessageB(*:2)))|'.
                'ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\Fixtures\\\Deeper\\\(?|Message\w(*:3))'.
            '}su',
            [1 => true, 2 => false, 3 => true],
            ['my_bar' => 'foo']
        );

        self::assertEquals(PolicyGuard::PERMISSION_ABSTAIN, $manager->decide(new \stdClass()));
        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageA(1)));
        self::assertEquals(PolicyGuard::PERMISSION_DENY, $manager->decide(new MessageB(2)));
        self::assertEquals(PolicyGuard::PERMISSION_ALLOW, $manager->decide(new MessageC(2)));
    }
}
