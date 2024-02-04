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
use Faker\Generator as FakerGenerator;
use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Application\Command\BatchCommand;
use ParkManager\Application\Command\User\RegisterUser;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class UserFixtures extends Fixture
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private PasswordHasherFactoryInterface $hasherFactory,
        private FakerGenerator $faker
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = [];

        foreach (range(1, 20) as $i) {
            $users[] = $user = new RegisterUser(
                UserId::create(),
                new EmailAddress($this->faker->unique()->email()),
                $this->faker->unique()->name(),
                $this->hasherFactory->getPasswordHasher(SecurityUser::class)->hash($this->faker->password(8))
            );

            if ($this->faker->randomDigit() % 2 === 0) {
                $user->requireNewPassword();
            }
        }

        $this->commandBus->dispatch(
            new BatchCommand(
                new RegisterUser(
                    UserId::create(),
                    new EmailAddress('jane@example.com'),
                    'Jane, Doe',
                    $this->hasherFactory->getPasswordHasher(SecurityUser::class)->hash('&ltr@Sec3re!+')
                ),
                ...$users
            )
        );
    }
}
