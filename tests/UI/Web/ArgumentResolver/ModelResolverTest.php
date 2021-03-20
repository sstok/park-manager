<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\ArgumentResolver;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use ParkManager\UI\Web\ArgumentResolver\ModelResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use const false;

/**
 * @internal
 */
final class ModelResolverTest extends TestCase
{
    private ModelResolver $resolver;

    protected function setUp(): void
    {
        $container = new Container();
        $container->set(Space::class, new SpaceRepositoryMock([
            SpaceRepositoryMock::createSpace(SpaceRepositoryMock::ID1),
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
        self::assertTrue($this->resolver->supports($request, $this->createArgumentMetadata(EmailAddress::class, false, true)));

        // Unsupported
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(DomainName::class)));
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(Request::class)));
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(EmailAddress::class, true)));
        self::assertFalse($this->resolver->supports($request, $this->createArgumentMetadata(null)));
    }

    private function createArgumentMetadata(?string $type, bool $isVariadic = false, bool $isNullable = false): ArgumentMetadata
    {
        return new ArgumentMetadata('id', $type, $isVariadic, false, null, $isNullable);
    }

    /** @test */
    public function it_gets_entity_from_repository(): void
    {
        $this->assertResolvedEntityEqualsId(Space::class, SpaceRepositoryMock::ID1);
        $this->assertResolvedEntityEqualsId(User::class, null, UserRepositoryMock::USER_ID1);
    }

    private function assertResolvedEntityEqualsId(string $class, ?string $id, $default = null): void
    {
        $request = new Request();

        if ($id !== null) {
            $request->attributes->set('id', $id);
        }

        $resolved = [];
        $argumentMetadata = new ArgumentMetadata('id', $class, false, $default !== null, $default);

        foreach ($this->resolver->resolve($request, $argumentMetadata) as $value) {
            $resolved[] = $value;
        }

        self::assertCount(1, $resolved);
        self::assertInstanceof($class, $resolved[0]);
        self::assertEquals($id ?? $default, $resolved[0]->id->toString());
    }

    /** @test */
    public function it_gets_model(): void
    {
        $this->assertResolvedModelEquals(UserId::class, UserRepositoryMock::USER_ID1, UserId::fromString(UserRepositoryMock::USER_ID1));
        $this->assertResolvedModelEquals(SpaceId::class, SpaceRepositoryMock::ID1, SpaceId::fromString(SpaceRepositoryMock::ID1));
        $this->assertResolvedModelEquals(SpaceId::class, null, SpaceId::fromString(SpaceRepositoryMock::ID1), SpaceRepositoryMock::ID1);
        $this->assertResolvedModelEquals(EmailAddress::class, 'jane@example.con', new EmailAddress('jane@example.con'));
    }

    private function assertResolvedModelEquals(string $class, ?string $id, object $expected, $default = null): void
    {
        $request = new Request();

        if ($id !== null) {
            $request->attributes->set('id', $id);
        }

        $resolved = [];
        $argumentMetadata = new ArgumentMetadata('id', $class, false, $default !== null, $default);

        foreach ($this->resolver->resolve($request, $argumentMetadata) as $value) {
            $resolved[] = $value;
        }

        self::assertCount(1, $resolved);
        self::assertInstanceof($class, $resolved[0]);
        self::assertEquals($expected, $resolved[0]);
    }

    /** @test */
    public function it_throws_when_resolving_null_type(): void
    {
        $request = new Request();

        $this->expectExceptionObject(new RuntimeException('Value/type for argument "id" cannot be null.'));

        foreach ($this->resolver->resolve($request, $this->createArgumentMetadata(null)) as $v) {
            echo $v; // no-op, but loop needed to start the generator.
        }
    }

    /** @test */
    public function it_throws_when_resolving_with_no_value_or_default(): void
    {
        $request = new Request();
        $request->attributes->set('id', null);

        $this->expectExceptionObject(new RuntimeException('Value/type for argument "id" cannot be null.'));

        foreach ($this->resolver->resolve($request, $this->createArgumentMetadata(UserId::class)) as $v) {
            echo $v; // no-op, but loop needed to start the generator.
        }
    }
}
