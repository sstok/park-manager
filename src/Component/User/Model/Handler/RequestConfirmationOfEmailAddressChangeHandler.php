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

namespace ParkManager\Component\User\Model\Handler;

use ParkManager\Component\Security\Token\SplitToken;
use ParkManager\Component\User\Model\Command\RequestConfirmationOfEmailAddressChange;
use ParkManager\Component\User\Model\Service\EmailAddressChangeConfirmationMailer;
use ParkManager\Component\User\Model\UserCollection;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RequestConfirmationOfEmailAddressChangeHandler
{
    private $userCollection;
    private $confirmationMailer;
    private $maxTokenLife;

    /**
     * Constructor.
     *
     * @param UserCollection                       $userCollection
     * @param EmailAddressChangeConfirmationMailer $confirmationMailer
     * @param int                                  $maxTokenLife       Maximum life-time in seconds (default is 'one hour')
     */
    public function __construct(
        UserCollection $userCollection,
        EmailAddressChangeConfirmationMailer $confirmationMailer,
        int $maxTokenLife = 3600
    ) {
        $this->maxTokenLife = $maxTokenLife;
        $this->userCollection = $userCollection;
        $this->confirmationMailer = $confirmationMailer;
    }

    public function __invoke(RequestConfirmationOfEmailAddressChange $command): void
    {
        $canonicalEmail = $command->canonicalEmail();

        if (null !== $this->userCollection->findByEmailAddress($canonicalEmail)) {
            // E-mail address is already in use by (another) user. To prevent exposing existence simply do nothing.
            // This also covers when the e-mail address was not actually changed.
            return;
        }

        $id = $command->id();
        $email = $command->email();
        $user = $this->userCollection->get($id);

        $splitToken = SplitToken::generate($id->toString());
        $tokenExpiration = new \DateTimeImmutable('+ '.$this->maxTokenLife.' seconds');

        if ($user->setConfirmationOfEmailAddressChange($email, $canonicalEmail, $splitToken->toValueHolder($tokenExpiration))) {
            $this->userCollection->save($user);
            $this->confirmationMailer->send($email, $splitToken, $tokenExpiration);
        }
    }
}
