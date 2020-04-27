<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ParkManager\Application\Command\BatchCommand;
use ParkManager\Application\Command\User\RegisterUser;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class UserFixtures extends Fixture
{
    /** @var MessageBusInterface */
    private $commandBus;

    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    public function __construct(MessageBusInterface $commandBus, EncoderFactoryInterface $encoderFactory)
    {
        $this->commandBus = $commandBus;
        $this->encoderFactory = $encoderFactory;
    }

    public function load(ObjectManager $manager): void
    {
        $this->commandBus->dispatch(
            new BatchCommand(
                new RegisterUser(
                    UserId::create(),
                    new EmailAddress('jane@example.com'),
                    'Jane, Doe',
                    $this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword('&ltr@Sec3re!+', null)
                )
            )
        );
    }
}
