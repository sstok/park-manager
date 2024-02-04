<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\UI\Web\Form\Type\User\Admin\ChangeUserPasswordForm;
use ParkManager\UI\Web\Form\Type\User\Admin\UserSecurityLevelForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SecuritySettingsAction extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/user/{id}/security-settings', name: 'park_manager.admin.user_security_settings', methods: ['GET', 'POST', 'HEAD'])]
    public function __invoke(Request $request, User $id, UserInterface $securityUser): Response
    {
        if (UserId::fromString($securityUser->getId())->equals($id->id)) {
            return $this->render('error.html.twig', ['message_translate' => new TranslatableMessage('user_management.self_edit_error')])
                ->setStatusCode(Response::HTTP_FORBIDDEN);
        }

        $changeUserLevel = $this->createForm(UserSecurityLevelForm::class, null, ['user' => $id]);
        $changeUserLevel->handleRequest($request);

        if ($changeUserLevel->isSubmitted() && $changeUserLevel->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.user_level_changed'));

            return $this->redirectToRoute('park_manager.admin.show_user', ['user' => $id->id->toString()]);
        }

        $changePasswordForm = $this->createForm(ChangeUserPasswordForm::class, $id);
        $changePasswordForm->handleRequest($request);

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.user_password_changed'));

            return $this->redirectToRoute('park_manager.admin.show_user', ['user' => $id->id->toString()]);
        }

        return $this->render('admin/user/security_settings.html.twig', [
            'change_user_level_form' => $changeUserLevel,
            'change_password_form' => $changePasswordForm,
            'user' => $id,
        ]);
    }
}
