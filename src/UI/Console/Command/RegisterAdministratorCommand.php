<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Console\Command;

use InvalidArgumentException;
use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface as MessageBus;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterAdministratorCommand extends Command
{
    protected static $defaultName = 'park-manager:administrator:register';

    private ValidatorInterface $validator;
    private EncoderFactoryInterface $passwordEncoder;
    private MessageBus $commandBus;

    public function __construct(ValidatorInterface $validator, EncoderFactoryInterface $passwordEncoder, MessageBus $commandBus)
    {
        parent::__construct();

        $this->validator = $validator;
        $this->passwordEncoder = $passwordEncoder;
        $this->commandBus = $commandBus;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Registers a new Administrator user')
            ->setHelp(<<<'EOT'
                The <info>%command.name%</info> command registers a new Administrator user.
                EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $displayName = $io->ask('Display name');
        $email = $io->ask('E-mail address', null, function ($value) {
            $violationList = $this->validator->validate($value, [new NotBlank(), new Email()]);

            if ($violationList->count() > 0) {
                /** @psalm-suppress InvalidCast */
                throw new InvalidArgumentException((string) $violationList);
            }

            return $value;
        });

        $password = $this->passwordEncoder->getEncoder(SecurityUser::class)
            ->encodePassword($io->askHidden('Password'), '');

        $this->commandBus->dispatch(
            new RegisterAdministrator(UserId::create(), $email, $displayName, $password)
        );

        $io->success('Administrator was registered.');

        return 0;
    }
}
