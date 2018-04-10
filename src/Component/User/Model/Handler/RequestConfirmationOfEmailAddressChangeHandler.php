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

use ParkManager\Component\Security\Token\SplitTokenFactory;
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
     * @var SplitTokenFactory
     */
    private $splitTokenFactory;

    /**
     * Constructor.
     *
     * @param UserCollection                       $userCollection
     * @param EmailAddressChangeConfirmationMailer $confirmationMailer
     * @param SplitTokenFactory                    $splitTokenFactory
     * @param int                                  $maxTokenLife       Maximum life-time in seconds (default is 'one hour')
     */
    public function __construct(
        UserCollection $userCollection,
        EmailAddressChangeConfirmationMailer $confirmationMailer,
        SplitTokenFactory $splitTokenFactory,
        int $maxTokenLife = 3600
    ) {
        $this->maxTokenLife = $maxTokenLife;
        $this->userCollection = $userCollection;
        $this->splitTokenFactory = $splitTokenFactory;
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

        $tokenExpiration = new \DateTimeImmutable('+ '.$this->maxTokenLife.' seconds');
        $splitToken = $this->splitTokenFactory->generate($id->toString(), $tokenExpiration);

        if ($user->setConfirmationOfEmailAddressChange($email, $canonicalEmail, $splitToken->toValueHolder())) {
            $this->userCollection->save($user);
            $this->confirmationMailer->send($email, $splitToken, $tokenExpiration);
        }
    }
}
