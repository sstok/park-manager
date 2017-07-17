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

namespace ParkManager\Bundle\CoreBundle\Cli\Command;

use ParkManager\Component\Core\Model\Command\RegisterAdministrator;
use ParkManager\Component\User\Model\UserId;
use ParkManager\Component\User\Security\Argon2iPasswordEncoder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\{Email, NotBlank};

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterAdministratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('park-manager:administrator:register')
            ->setDescription('Registers a new Administrator user')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command registers a new Administrator user.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $validator = $container->get('validator');
        $io = new SymfonyStyle($input, $output);

        $firstName = $io->ask('First name');
        $lastName = $io->ask('Last name');
        $email = $io->ask('E-mail address', null, function ($value) use ($validator) {
            $violationList = $validator->validate($value, [new NotBlank(), new Email()]);

            if ($violationList->count() > 0) {
                throw new \InvalidArgumentException((string) $violationList);
            }

            return $value;
        });

        $password = $container->get(Argon2iPasswordEncoder::class)->encodePassword($io->askHidden('Password'), '');
        $container->get('prooph_service_bus.administrator.command_bus')->dispatch(
            new RegisterAdministrator(UserId::create()->toString(), $email, $firstName, $lastName, $password)
        );

        $io->success('Administrator was registered.');

        return 0;
    }
}
