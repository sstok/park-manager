<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Application\Mailer\User\PasswordResetMailer;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\UserRepository;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

final class RequestPasswordResetHandler
{
    /**
     * @param int $tokenTTL Maximum life-time in seconds (default is 'one hour')
     */
    public function __construct(
        private UserRepository $repository,
        private SplitTokenFactory $tokenFactory,
        private PasswordResetMailer $mailer,
        private int $tokenTTL = 3600
    ) {}

    public function __invoke(RequestPasswordReset $command): void
    {
        // Create the token always to prevent leaking timing information,
        // when no user exists the token would have not been generated.
        // Thus leaking timing information about existence.
        //
        // It's still possible persistence may leak timing information
        // but leaking persistence timing is less risky.
        $splitToken = $this->tokenFactory->generate()->expireAt(
            new \DateTimeImmutable('+ ' . $this->tokenTTL . ' seconds')
        );

        try {
            $user = $this->repository->getByEmail($command->email);
        } catch (UserNotFound) {
            // No user with this email address. To prevent exposing existence simply do nothing.
            return;
        }

        if ($user->requestPasswordReset($splitToken)) {
            $this->repository->save($user);

            $this->mailer->send($user->email, $splitToken);
        }
    }
}
