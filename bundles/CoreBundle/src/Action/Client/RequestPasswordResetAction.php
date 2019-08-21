<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Action\Client;

use ParkManager\Bundle\CoreBundle\UseCase\Client\RequestPasswordReset;
use ParkManager\Bundle\CoreBundle\Common\TwigResponse;
use ParkManager\Bundle\CoreBundle\Form\Type\Security\RequestPasswordResetType;
use Rollerworks\Bundle\RouteAutofillBundle\Response\RouteRedirectResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class RequestPasswordResetAction
{
    public function __invoke(Request $request, FormFactoryInterface $formFactory): object
    {
        $form = $formFactory->create(RequestPasswordResetType::class, null, [
            'command_message_factory' => static function (array $data) {
                return new RequestPasswordReset($data['email']);
            },
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.client.security_login')
                ->withFlash('success', 'flash.password_reset_send');
        }

        $response = new TwigResponse('@ParkManagerCore/client/security/password_reset.html.twig', $form);
        $response->setPrivate();
        $response->setMaxAge(1);

        return $response;
    }
}
