<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\UserId;
use ParkManager\UI\Web\Form\Type\User\RegisterUserForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RegisterUserAction extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/user/register', name: 'park_manager.admin.register_user', methods: ['GET', 'POST', 'HEAD'])]
    public function __invoke(Request $request): Response
    {
        $userId = UserId::create();

        $form = $this->createForm(RegisterUserForm::class, null, ['user_id' => $userId]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.user_registered'));

            return $this->redirectToRoute('park_manager.admin.show_user', ['user' => $userId->toString()]);
        }

        return $this->render('admin/user/register.html.twig', ['form' => $form]);
    }
}
