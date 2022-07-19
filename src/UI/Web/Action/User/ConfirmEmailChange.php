<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\User;

use ParkManager\Application\Command\User\ConfirmEmailAddressChange;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class ConfirmEmailChange extends AbstractController
{
    #[Route(path: '/confirm-email-address-change/{token}', name: 'park_manager.confirm_email_address_change', requirements: ['token' => '.+'], methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, SplitToken $token, MessageBusInterface $messageBus): object
    {
        $messageBus->dispatch(new ConfirmEmailAddressChange($token));

        return new Response('IT IS DONE!');
    }
}
