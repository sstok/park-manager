<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\UI\Web\Form\Type\User\Admin\ChangeUserEmailAddressForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class ChangeUserEmailAddressAction
{
    #[Security("is_granted('ROLE_SUPER_ADMIN')")]
    #[Route(path: '/user/{user}/change-email-address', name: 'park_manager.admin.user_change_email_address', methods: ['GET', 'POST', 'HEAD'])]
    public function __invoke(Request $request, User $user, UserInterface $securityUser, FormFactoryInterface $formFactory): TwigResponse | RouteRedirectResponse
    {
        if (UserId::fromString($securityUser->getId())->equals($user->id)) {
            return new TwigResponse('error.html.twig', ['message_translate' => new TranslatableMessage('user_management.self_edit_error')], Response::HTTP_FORBIDDEN);
        }

        $form = $formFactory->create(ChangeUserEmailAddressForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.show_user', ['user' => $user->id->toString()])
                ->withFlash('success', $form->get('require_confirm')->getData() ? 'flash.email_address_change_requested' : 'flash.email_address_changed')
            ;
        }

        return new TwigResponse('admin/user/change_email_address.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
