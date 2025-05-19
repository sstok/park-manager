<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Service;

use ParkManager\Application\Service\SystemGateway;
use ParkManager\Application\Service\SystemGateway\OperationResult;
use ParkManager\Application\Service\SystemGateway\SystemCommand;
use ParkManager\Application\Service\SystemGateway\SystemQuery;
use ParkManager\Application\Service\SystemGateway\Webhosting\CreateMailbox;
use ParkManager\Application\Service\SystemGateway\Webhosting\CreateMailboxResult;
use ParkManager\Application\Service\SystemGateway\Webhosting\RegisterSystemUser;
use ParkManager\Application\Service\SystemGateway\Webhosting\RegisterSystemUserResult;

final class SystemGatewayImpl implements SystemGateway
{
    public function execute(SystemCommand $command): OperationResult
    {
        // XXX Mocked-up results.

        return match ($command::class) {
            RegisterSystemUser::class => new RegisterSystemUserResult(['id' => $id = mt_rand(), 'groups' => [500], 'homedir' => '/data/site_' . $id]),
            CreateMailbox::class => new CreateMailboxResult(['storage' => '/data/mail/' . str_replace('-', '', (string) $command->getArguments()['mailbox_id'])]),
            default => throw new \InvalidArgumentException(\sprintf('Unsupported SystemCommand %s', $command::class)),
        };
    }

    public function query(SystemQuery $command): OperationResult
    {
        throw new \InvalidArgumentException(\sprintf('Unsupported SystemQuery %s', $command::class));
    }
}
