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
use ParkManager\UI\Web\Form\Type\User\Admin\ChangeUserEmailAddressForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ChangeUserEmailAddressAction extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/user/{user}/change-email-address', name: 'park_manager.admin.user_change_email_address', methods: ['GET', 'POST', 'HEAD'])]
    public function __invoke(Request $request, User $user, UserInterface $securityUser): Response
    {
        $form = $this->createForm(ChangeUserEmailAddressForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success',
                $form->get('require_confirm')->getData() ?
                    new TranslatableMessage('flash.email_address_change_requested') :
                    new TranslatableMessage('flash.email_address_changed')
            );

            return $this->redirectToRoute('park_manager.admin.show_user', ['user' => $user->id->toString()]);
        }

        return $this->render('admin/user/change_email_address.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
