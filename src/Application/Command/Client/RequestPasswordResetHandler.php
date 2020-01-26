<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Client;

use DateTimeImmutable;
use ParkManager\Application\Mailer\Client\PasswordResetMailer;
use ParkManager\Domain\Client\ClientRepository;
use ParkManager\Domain\Client\Exception\ClientNotFound;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

final class RequestPasswordResetHandler
{
    /** @var ClientRepository */
    private $repository;

    /** @var SplitTokenFactory */
    private $tokenFactory;

    /** @var PasswordResetMailer */
    private $mailer;

    /** @var int */
    private $tokenTTL;

    public function __construct(ClientRepository $clients, SplitTokenFactory $tokenFactory, PasswordResetMailer $mailer, int $tokenTTL = 3600)
    {
        $this->repository = $clients;
        $this->tokenFactory = $tokenFactory;
        $this->tokenTTL = $tokenTTL;
        $this->mailer = $mailer;
    }

    public function __invoke(RequestPasswordReset $command): void
    {
        // Create the token always to prevent leaking timing information,
        // when no client exists the token would have not been generated.
        // Thus leaking timing information about existence.
        //
        // It's still possible persistence may leak timing information
        // but leaking persistence timing is less risky.
        $splitToken = $this->tokenFactory->generate()->expireAt(
            new DateTimeImmutable('+ ' . $this->tokenTTL . ' seconds')
        );

        try {
            $client = $this->repository->getByEmail($command->email());
        } catch (ClientNotFound $e) {
            // No account with this e-mail address. To prevent exposing existence simply do nothing.
            return;
        }

        if ($client->requestPasswordReset($splitToken)) {
            $this->repository->save($client);

            $this->mailer->send($client->getEmail(), $splitToken);
        }
    }
}
