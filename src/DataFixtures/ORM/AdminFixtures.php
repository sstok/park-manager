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
use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\BatchCommand;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerRepository;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class AdminFixtures extends Fixture
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private PasswordHasherFactoryInterface $encoderFactory,
        private FakerGenerator $faker,
        private OrganizationRepository $organizationRepository,
        private OwnerRepository $ownerRepository
    ) {}

    public function load(ObjectManager $manager): void
    {
        // XXX This needs to be moved to an installer script as it's ALWAYS needed.
        $this->organizationRepository->save($adminOrg = new Organization(OrganizationId::fromString(OrganizationId::ADMIN_ORG), 'Administrators'));
        $this->ownerRepository->save(Owner::byOrganization($adminOrg));

        $admins = [];

        foreach (range(1, 6) as $i) {
            $admins[] = $admin = new RegisterAdministrator(
                UserId::create(),
                new EmailAddress($this->faker->unique()->email()),
                $this->faker->unique()->name(),
                $this->encoderFactory->getPasswordHasher(SecurityUser::class)->hash($this->faker->password(8))
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
                    UserId::create(),
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
        return $this->encoderFactory->getPasswordHasher(SecurityUser::class)->hash($password);
    }
}
