<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\ArgumentResolver;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use ParkManager\UI\Web\ArgumentResolver\ModelResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @internal
 */
final class ModelResolverTest extends TestCase
{
    /** @var ModelResolver */
    private $resolver;

    protected function setUp(): void
    {
        $container = new Container();
        $container->set(Space::class, new SpaceRepositoryMock([
            Space::registerWithCustomConstraints(SpaceId::fromString(SpaceRepositoryMock::ID1), null, new Constraints()),
        ]));
        $container->set(User::class, new UserRepositoryMock([
            User::register(UserId::fromString(UserRepositoryMock::USER_ID1), new EmailAddress('jane@example.com'), 'Jane', 'He'),
        ]));

        $this->resolver = new ModelResolver($container, [
            SpaceId::class => 'fromString',
            UserId::class => 'fromString',
        ]);
    }

    /** @test */
    public function it_supports_registered_models(): void
    {
        $request = new Request();
        self::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(User::class)));
        self::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(UserId::class)));
        self::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(UserId::class)));
        self::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(EmailAddress::class)));

        // Unsupported
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(WebhostingDomainName::class)));
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(Request::class)));
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(EmailAddress::class, true)));
    }

    private function createArgumentMetadata(string $type, $isVariadic = false): ArgumentMetadata
    {
        return new ArgumentMetadata('id', $type, $isVariadic, false, null, false);
    }

    /** @test */
    public function it_gets_entity_from_repository(): void
    {
        $this->assertResolvedEntityEqualsId(Space::class, SpaceRepositoryMock::ID1);
        $this->assertResolvedEntityEqualsId(User::class, UserRepositoryMock::USER_ID1);
    }

    private function assertResolvedEntityEqualsId(string $class, string $id): void
    {
        $request = new Request();
        $request->attributes->set('id', $id);
        $resolved = [];

        foreach ($this->resolver->resolve($request, $this->createArgumentMetadata($class)) as $value) {
            $resolved[] = $value;
        }

        self::assertCount(1, $resolved);
        self::assertInstanceof($class, $resolved[0]);

        // XXX Temporary solution till all models are converted to typed properties
        if (\method_exists($resolved[0], 'getId')) {
            self::assertEquals($id, $resolved[0]->getId()->toString());
        } else {
            self::assertEquals($id, $resolved[0]->id->toString());
        }
    }

    /** @test */
    public function it_gets_model(): void
    {
        $this->assertResolvedModelEquals(UserId::class, UserRepositoryMock::USER_ID1, UserId::fromString(UserRepositoryMock::USER_ID1));
        $this->assertResolvedModelEquals(SpaceId::class, SpaceRepositoryMock::ID1, SpaceId::fromString(SpaceRepositoryMock::ID1));
        $this->assertResolvedModelEquals(EmailAddress::class, 'jane@example.con', new EmailAddress('jane@example.con'));
    }

    private function assertResolvedModelEquals(string $class, string $id, object $expected): void
    {
        $request = new Request();
        $request->attributes->set('id', $id);
        $resolved = [];

        foreach ($this->resolver->resolve($request, $this->createArgumentMetadata($class)) as $value) {
            $resolved[] = $value;
        }

        self::assertCount(1, $resolved);
        self::assertInstanceof($class, $resolved[0]);
        self::assertEquals($expected, $resolved[0]);
    }
}
