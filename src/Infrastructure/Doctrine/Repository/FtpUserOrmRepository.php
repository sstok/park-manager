<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Ftp\Exception\FtpUsernameAlreadyExists;
use ParkManager\Domain\Webhosting\Ftp\Exception\FtpUserNotFound;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @extends EntityRepository<FtpUser>
 */
final class FtpUserOrmRepository extends EntityRepository implements FtpUserRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, FtpUser::class);
    }

    public function get(FtpUserId $id): FtpUser
    {
        $user = $this->find($id->toString());

        if ($user === null) {
            throw FtpUserNotFound::withId($id);
        }

        return $user;
    }

    public function all(SpaceId $space): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('u')
                ->where('u.space = :space')
                ->setParameter('space', $space->toString()),
            'u'
        );
    }

    public function save(FtpUser $user): void
    {
        /** @var FtpUser|null $existing */
        $existing = $this->createQueryBuilder('d')
            ->andWhere('u.username = :username AND u.domainName = :domainName')
            ->getQuery()
            ->setParameter('username', $user->username)
            ->setParameter('domainName', $existing->domainName->id->toString())
            ->getOneOrNullResult()
        ;

        if ($existing !== null && ! $existing->id->equals($user->id)) {
            throw new FtpUsernameAlreadyExists($user->username, $existing->domainName->namePair, $existing->id);
        }

        $this->_em->persist($user);
    }

    public function remove(FtpUser $user): void
    {
        $this->_em->remove($user);
    }
}
