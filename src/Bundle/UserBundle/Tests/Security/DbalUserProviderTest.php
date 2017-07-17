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

namespace ParkManager\Bundle\UserBundle\Tests\Security;

use Doctrine\DBAL\Connection;
use ParkManager\Bundle\UserBundle\Security\DbalUserProvider;
use ParkManager\Bundle\UserBundle\Tests\Fixtures\TestSecurityUser;
use ParkManager\Component\User\Canonicalizer\SimpleEmailCanonicalizer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @internal
 *
 * @functional
 */
final class DbalUserProviderTest extends WebTestCase
{
    private const ID1 = '00fdb924-693b-11e7-bc63-acbc32b58315';
    private const ID2 = '058caf72-693b-11e7-86bd-acbc32b58315';

    private const PASSWORD_1 = '$argon2i$v=19$m=32768,t=4,p=1$fHNWQH28JdQfHBAO3C1POw$KQFkjjHnEg412zJ4MwVNEvKeeoDrqm5c75pQcizWBsU';
    private const PASSWORD_2 = '$argon2i$v=19$m=32768,t=4,p=1$xtVzo4Fd7Ux05xZPmf8JAQ$EkWmxo+CVSNgQUj44ejhqcDbI1w8lbAIrsKrpPuyGgw';

    /** @test */
    public function it_loads_user_by_email_address()
    {
        $client = self::createClient();
        $container = $client->getContainer();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection('default');
        $connection->setAutoCommit(false);
        $connection->beginTransaction();
        $this->setUpDb($connection);

        $loader = new DbalUserProvider($connection, new SimpleEmailCanonicalizer(), 'test_user', TestSecurityUser::class);

        self::assertEquals(new TestSecurityUser(self::ID1, self::PASSWORD_1, true, ['ROLE_USER']), $loader->loadUserByUsername('jane@example.com'));
        self::assertEquals(new TestSecurityUser(self::ID2, self::PASSWORD_2, false, ['ROLE_USER', 'ROLE_ADMIN']), $loader->loadUserByUsername('john@example.com'));
    }

    /** @test */
    public function it_throws_UsernameNotFoundException_when_no_user_was_found()
    {
        $client = self::createClient();
        $container = $client->getContainer();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection('default');
        $connection->setAutoCommit(false);
        $connection->beginTransaction();
        $this->setUpDb($connection);

        $loader = new DbalUserProvider($connection, new SimpleEmailCanonicalizer(), 'test_user', TestSecurityUser::class);

        $this->expectException(UsernameNotFoundException::class);

        $loader->loadUserByUsername('beep@bong.com');
    }

    /** @test */
    public function it_reloads_user_by_id()
    {
        $client = self::createClient();
        $container = $client->getContainer();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection('default');
        $connection->setAutoCommit(false);
        $connection->beginTransaction();
        $this->setUpDb($connection);

        $loader = new DbalUserProvider($connection, new SimpleEmailCanonicalizer(), 'test_user', TestSecurityUser::class);
        $user1 = $loader->loadUserByUsername('jane@example.com');
        $user2 = $loader->loadUserByUsername('john@example.com');

        $connection->update(
            'test_user',
            ['email' => 'Jane-s@example.com', 'canonical_email' => 'jane-s@example.com', 'roles' => ['ROLE_USER', 'ROLE_ADMIN'], 'access_enabled' => false],
            ['id' => self::ID1],
            [
                'access_enabled' => 'boolean',
                'roles' => 'json_array',
            ]
        );

        $connection->update(
            'test_user',
            ['email' => 'Jon-s@example.com', 'canonical_email' => 'jon-s@example.com', 'roles' => ['ROLE_USER'], 'access_enabled' => true],
            ['id' => self::ID2],
            [
                'access_enabled' => 'boolean',
                'roles' => 'json_array',
            ]
        );

        self::assertEquals(new TestSecurityUser(self::ID1, self::PASSWORD_1, false, ['ROLE_USER', 'ROLE_ADMIN']), $loader->refreshUser($user1));
        self::assertEquals(new TestSecurityUser(self::ID2, self::PASSWORD_2, true, ['ROLE_USER']), $loader->refreshUser($user2));
    }

    /** @test */
    public function it_throws_UsernameNotFoundException_when_no_user_was_found_during_reload()
    {
        $client = self::createClient();
        $container = $client->getContainer();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection('default');
        $connection->setAutoCommit(false);
        $connection->beginTransaction();
        $this->setUpDb($connection);

        $loader = new DbalUserProvider($connection, new SimpleEmailCanonicalizer(), 'test_user', TestSecurityUser::class);
        $user1 = $loader->loadUserByUsername('jane@example.com');

        $connection->delete('test_user', ['id' => self::ID1]);

        $this->expectException(UsernameNotFoundException::class);

        $loader->refreshUser($user1);
    }

    private function setUpDb(Connection $connection): void
    {
        $connection->executeUpdate(
            <<<'DDL'
CREATE TEMPORARY TABLE test_user (
    id uuid NOT NULL,
    email text NOT NULL,
    canonical_email text NOT NULL,
    auth_password text,
    access_enabled boolean NOT NULL,
    roles json NOT NULL
);

ALTER TABLE ONLY test_user
    ADD CONSTRAINT test_user_pkey PRIMARY KEY (id);
DDL
        );

        $connection->insert(
            'test_user',
            [
                'id' => self::ID1,
                'email' => 'JanE@example.com',
                'canonical_email' => 'jane@example.com',
                'auth_password' => self::PASSWORD_1,
                'access_enabled' => true,
                'roles' => ['ROLE_USER'],
            ],
            [
                'access_enabled' => 'boolean',
                'roles' => 'json_array',
            ]
        );

        $connection->insert(
            'test_user',
            [
                'id' => self::ID2,
                'email' => 'John@example.com',
                'canonical_email' => 'john@example.com',
                'auth_password' => self::PASSWORD_2,
                'access_enabled' => false,
                'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            ],
            [
                'access_enabled' => 'boolean',
                'roles' => 'json_array',
            ]
        );
    }
}
