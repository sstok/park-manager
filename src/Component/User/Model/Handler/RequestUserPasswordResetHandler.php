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
use ParkManager\Component\User\Canonicalizer\Canonicalizer;
use ParkManager\Component\User\Model\Command\RequestUserPasswordReset;
use ParkManager\Component\User\Model\Service\PasswordResetMailer;
use ParkManager\Component\User\Model\UserCollection;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RequestUserPasswordResetHandler
{
    private $userCollection;
    private $emailCanonicalizer;
    private $passwordResetMailer;
    private $splitTokenFactory;
    private $maxTokenLife;

    /**
     * @param UserCollection      $userCollection
     * @param Canonicalizer       $emailCanonicalizer
     * @param PasswordResetMailer $passwordResetMailer
     * @param SplitTokenFactory   $splitTokenFactory
     * @param int                 $maxTokenLife        Maximum life-time in seconds (default is 'one hour')
     */
    public function __construct(
        UserCollection $userCollection,
        Canonicalizer $emailCanonicalizer,
        PasswordResetMailer $passwordResetMailer,
        SplitTokenFactory $splitTokenFactory,
        int $maxTokenLife = 3600
    ) {
        $this->userCollection = $userCollection;
        $this->emailCanonicalizer = $emailCanonicalizer;
        $this->passwordResetMailer = $passwordResetMailer;
        $this->splitTokenFactory = $splitTokenFactory;
        $this->maxTokenLife = $maxTokenLife;
    }

    public function __invoke(RequestUserPasswordReset $command): void
    {
        $email = $command->email();
        $canonicalEmail = $this->emailCanonicalizer->canonicalize($email);

        if (null === ($user = $this->userCollection->findByEmailAddress($canonicalEmail))) {
            // No account with this e-mail address. To prevent exposing existence simply do nothing.
            return;
        }

        $tokenExpiration = new \DateTimeImmutable('+ '.$this->maxTokenLife.' seconds');
        $splitToken = $this->splitTokenFactory->generate($user->id()->toString(), $tokenExpiration);

        if ($user->setPasswordResetToken($splitToken->toValueHolder())) {
            $this->userCollection->save($user);
            $this->passwordResetMailer->send($email, $splitToken, $tokenExpiration);
        }
    }
}
