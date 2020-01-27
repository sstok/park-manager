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
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\UserRepository;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

final class RequestEmailAddressChangeHandler
{
    private $repository;
    private $confirmationMailer;
    private $splitTokenFactory;
    private $tokenTTL;

    /**
     * @param int $tokenTTL Maximum life-time in seconds (default is 'one hour')
     */
    public function __construct(UserRepository $repository, ConfirmationMailer $mailer, SplitTokenFactory $tokenFactory, int $tokenTTL = 3600)
    {
        $this->tokenTTL = $tokenTTL;
        $this->repository = $repository;
        $this->splitTokenFactory = $tokenFactory;
        $this->confirmationMailer = $mailer;
    }

    public function __invoke(RequestEmailAddressChange $command): void
    {
        $email = $command->email();

        try {
            $this->repository->getByEmail($email);

            // Email address is already in use by (another) user. To prevent exposing existence simply do nothing.
            // This also covers when the email address was not actually changed.
            return;
        } catch (UserNotFound $e) {
            // No-op
        }

        $id = $command->id();
        $user = $this->repository->get($id);

        $tokenExpiration = new DateTimeImmutable('+ ' . $this->tokenTTL . ' seconds');
        $splitToken = $this->splitTokenFactory->generate()->expireAt($tokenExpiration);

        if ($user->requestEmailChange($email, $splitToken)) {
            $this->repository->save($user);
            $this->confirmationMailer->send($email, $splitToken);
        }
    }
}
