<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\ArgumentResolver;

use ParkManager\Domain\Administrator\Administrator;
use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Tests\Mock\Domain\AdministratorRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
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
        $container->set(Administrator::class, new AdministratorRepositoryMock([
            Administrator::register(AdministratorId::fromString(AdministratorRepositoryMock::USER_ID1), new EmailAddress('jane@example.com'), 'Jane', 'He'),
        ]));
        $container->set(User::class, new UserRepositoryMock([
            User::register(UserId::fromString(UserRepositoryMock::USER_ID1), new EmailAddress('jane@example.com'), 'Jane', 'He'),
        ]));

        $this->resolver = new ModelResolver($container, [
            AdministratorId::class => 'fromString',
            UserId::class => 'fromString',
        ]);
    }

    /** @test */
    public function it_supports_registered_models(): void
    {
        $request = new Request();
        static::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(Administrator::class)));
        static::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(AdministratorId::class)));
        static::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(UserId::class)));
        static::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(EmailAddress::class)));

        // Unsupported
        static::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(Space::class)));
        static::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(Request::class)));
        static::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(EmailAddress::class, true)));
    }

    private function createArgumentMetadata(string $type, $isVariadic = false): ArgumentMetadata
    {
        return new ArgumentMetadata('id', $type, $isVariadic, false, null, false);
    }

    /** @test */
    public function it_gets_entity_from_repository(): void
    {
        $this->assertResolvedEntityEqualsId(Administrator::class, AdministratorRepositoryMock::USER_ID1);
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

        static::assertCount(1, $resolved);
        self::assertInstanceof($class, $resolved[0]);
        static::assertEquals($id, $resolved[0]->getId()->toString());
    }

    /** @test */
    public function it_gets_model(): void
    {
        $this->assertResolvedModelEquals(UserId::class, UserRepositoryMock::USER_ID1, UserId::fromString(UserRepositoryMock::USER_ID1));
        $this->assertResolvedModelEquals(AdministratorId::class, AdministratorRepositoryMock::USER_ID1, AdministratorId::fromString(AdministratorRepositoryMock::USER_ID1));
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

        static::assertCount(1, $resolved);
        self::assertInstanceof($class, $resolved[0]);
        static::assertEquals($expected, $resolved[0]);
    }
}
