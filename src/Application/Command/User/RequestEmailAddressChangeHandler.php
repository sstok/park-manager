<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use DateTimeImmutable;
use ParkManager\Application\Mailer\User\EmailAddressChangeRequestMailer as ConfirmationMailer;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\UserRepository;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

final class RequestEmailAddressChangeHandler
{
    /**
     * @param int $tokenTTL Maximum life-time in seconds (default is 'one hour')
     */
    public function __construct(
        private UserRepository $repository,
        private ConfirmationMailer $confirmationMailer,
        private SplitTokenFactory $splitTokenFactory,
        private int $tokenTTL = 3600
    ) {
    }

    public function __invoke(RequestEmailAddressChange $command): void
    {
        try {
            $existing = $this->repository->getByEmail($command->email);

            // It's possible only the name, casing or label is changed.
            // But the effective address is still has the same user.
            if (! $existing->id->equals($command->id)) {
                throw new EmailAddressAlreadyInUse($existing->id, $command->email);
            }
        } catch (UserNotFound) {
            // No-op
        }

        $user = $this->repository->get($command->id);

        $tokenExpiration = new DateTimeImmutable('+ ' . $this->tokenTTL . ' seconds');
        $splitToken = $this->splitTokenFactory->generate()->expireAt($tokenExpiration);

        if ($user->requestEmailChange($command->email, $splitToken)) {
            $this->repository->save($user);
            $this->confirmationMailer->send($command->email, $splitToken);
        }
    }
}
