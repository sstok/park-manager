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
use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\BatchCommand;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class AdminFixtures extends Fixture
{
    private MessageBusInterface $commandBus;
    private EncoderFactoryInterface $encoderFactory;
    private FakerGenerator $faker;

    public function __construct(MessageBusInterface $commandBus, EncoderFactoryInterface $encoderFactory, FakerGenerator $faker)
    {
        $this->commandBus = $commandBus;
        $this->encoderFactory = $encoderFactory;
        $this->faker = $faker;
    }

    public function load(ObjectManager $manager): void
    {
        $admins = [];

        foreach (\range(1, 6) as $i) {
            $admins[] = $admin = new RegisterAdministrator(
                UserId::create(),
                new EmailAddress($this->faker->unique()->email()),
                $this->faker->unique()->name(),
                $this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword($this->faker->password(8), null)
            );

            if ($this->faker->randomDigit() % 2 === 0) {
                $admin->asSuperAdmin();
            }

            if ($this->faker->randomDigit() % 2 === 0) {
                $admin->requireNewPassword();
            }
        }

        $this->commandBus->dispatch(
            new BatchCommand(
                (new RegisterAdministrator(
                    $id = UserId::create(),
                    new EmailAddress('janet@example.com'),
                    'Janet, Doe',
                    $this->encodePassword('&ltr@Sec3re!+')
                ))->asSuperAdmin(),
                ...$admins
            )
        );
    }

    private function encodePassword(string $password): string
    {
        return $this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword($password, null);
    }
}
