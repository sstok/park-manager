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
use ParkManager\UI\Web\Form\Type\User\Admin\ChangeUserPasswordForm;
use ParkManager\UI\Web\Form\Type\User\Admin\UserSecurityLevelForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class SecuritySettingsAction
{
    #[Security("is_granted('ROLE_SUPER_ADMIN')")]
    #[Route(path: '/user/{id}/security-settings', name: 'park_manager.admin.user_security_settings', methods: ['GET', 'POST', 'HEAD'])]
    public function __invoke(Request $request, User $id, UserInterface $securityUser, FormFactoryInterface $formFactory): TwigResponse | RouteRedirectResponse
    {
        if (UserId::fromString($securityUser->getId())->equals($id->id)) {
            return new TwigResponse('error.html.twig', ['message_translate' => new TranslatableMessage('user_management.self_edit_error')], Response::HTTP_FORBIDDEN);
        }

        $changeUserLevel = $formFactory->create(UserSecurityLevelForm::class, null, ['user' => $id]);
        $changeUserLevel->handleRequest($request);

        if ($changeUserLevel->isSubmitted() && $changeUserLevel->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.show_user', ['user' => $id->id->toString()])
                ->withFlash('success', 'flash.user_level_changed')
            ;
        }

        $changePasswordForm = $formFactory->create(ChangeUserPasswordForm::class, $id);
        $changePasswordForm->handleRequest($request);

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.show_user', ['user' => $id->id->toString()])
                ->withFlash('success', 'flash.user_password_changed')
            ;
        }

        return new TwigResponse('admin/user/security_settings.html.twig', [
            'change_user_level_form' => $changeUserLevel->createView(),
            'change_password_form' => $changePasswordForm->createView(),
            'user' => $id,
        ]);
    }
}
