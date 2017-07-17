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

namespace ParkManager\Bundle\UserBundle\ReadModel;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use ParkManager\Component\Security\Token\SplitTokenValueHolder;
use ParkManager\Component\User\Model\UserId;
use ParkManager\Component\User\ReadModel\User;
use ParkManager\Component\User\ReadModel\UserFinder;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class DbalUserFinder implements UserFinder
{
    protected $connection;

    /**
     * @var string
     */
    private $tableName;

    public function __construct(Connection $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function findByPasswordResetToken(string $selector): ?User
    {
        $result = $this->connection->fetchAssoc(
            'SELECT 
                id, 
                email, 
                access_enabled, 
                password_reset_verifier, 
                password_reset_expires_at, 
                password_reset_metadata
            FROM
                '.$this->tableName.'
            WHERE
                password_reset_selector = :selector AND password_reset_expires_at > NOW()',
            ['selector' => $selector]
        );

        if (empty($result['id'])) {
            return null;
        }

        $user = new User(UserId::fromString($result['id']));
        $user->email = $result['email'];
        $user->enabled = $this->getEnabledStatus($result);
        $user->passwordResetToken = new SplitTokenValueHolder(
            $selector,
            $result['password_reset_verifier'],
            $this->nullOrDatetime($result['password_reset_expires_at'])
        );

        return $user;
    }

    protected function getEnabledStatus(array $result): bool
    {
        return Type::getType(Type::BOOLEAN)->convertToPHPValue(
            $result['access_enabled'],
            $this->connection->getDatabasePlatform()
        );
    }

    protected function nullOrDatetime(?string $value)
    {
        if (null === $value) {
            return null;
        }

        return new \DateTimeImmutable($value);
    }
}
