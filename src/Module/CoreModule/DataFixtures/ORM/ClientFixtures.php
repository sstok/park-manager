<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\CoreModule\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use ParkManager\Module\CoreModule\Application\Command\BatchCommand;
use ParkManager\Module\CoreModule\Application\Command\Client\RegisterClient;
use ParkManager\Module\CoreModule\Domain\Client\ClientId;
use ParkManager\Module\CoreModule\Domain\Shared\EmailAddress;
use ParkManager\Module\CoreModule\Infrastructure\Security\ClientUser;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class ClientFixtures extends Fixture
{
    /** @var MessageBusInterface */
    private $commandBus;

    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    public function __construct(MessageBusInterface $commandBus, EncoderFactoryInterface $encoderFactory)
    {
        $this->commandBus     = $commandBus;
        $this->encoderFactory = $encoderFactory;
    }

    public function load(ObjectManager $manager)
    {
        $this->commandBus->dispatch(
            new BatchCommand(
                new RegisterClient(
                    ClientId::create(),
                    new EmailAddress('jane@example.com'),
                    'Janet, Doe',
                    $this->encoderFactory->getEncoder(ClientUser::class)->encodePassword('&ltr@Sec3re!+', null)
                )
            )
        );
    }
}
