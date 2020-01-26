<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin;

use ParkManager\Application\Command\Administrator\ConfirmPasswordReset;
use ParkManager\Infrastructure\Security\AdministratorUser;
use ParkManager\UI\Web\Form\Type\Security\ConfirmPasswordResetType;
use ParkManager\UI\Web\Response\TwigResponse;
use Rollerworks\Bundle\RouteAutofillBundle\Response\RouteRedirectResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ConfirmPasswordResetAction
{
    /**
     * @Route(
     *     path="/password-reset/confirm/{token}",
     *     name="park_manager.admin.security_confirm_password_reset",
     *     requirements={"token": ".+"},
     *     methods={"GET", "POST"}
     * )
     *
     * @return RouteRedirectResponse|TwigResponse
     */
    public function __invoke(Request $request, string $token, FormFactoryInterface $formFactory)
    {
        $form = $formFactory->create(ConfirmPasswordResetType::class, ['reset_token' => $token], [
            'request_route' => 'park_manager.client.security_request_password_reset',
            'user_class' => AdministratorUser::class,
            'command_factory' => static function (array $data) {
                return new ConfirmPasswordReset($data['reset_token'], $data['password']);
            },
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return new RouteRedirectResponse('park_manager.admin.security_login');
        }

        $response = new TwigResponse('admin/security/password_reset_confirm.html.twig', $form);
        $response->setPrivate();
        $response->setMaxAge(1);

        return $response;
    }
}
