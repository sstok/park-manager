<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\Application\Command\User\DeleteRegistration;
use ParkManager\Domain\User\User;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RemoveUserAction
{
    /**
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     *
     * @Route(
     *     path="/user/{id}/remove",
     *     methods={"GET", "POST", "HEAD"},
     *     name="park_manager.admin.remove_user"
     * )
     */
    public function __invoke(Request $request, User $id, FormFactoryInterface $formFactory)
    {
        $form = $formFactory->create(ConfirmationForm::class, null, [
            'confirmation_title' => 'user_management.remove.heading',
            'confirmation_message' => 'user_management.remove.confirm_warning',
            'confirmation_label' => 'label.remove',
            'cancel_route' => [
                'name' => 'park_manager.admin.show_user',
                'arguments' => ['user' => $id->id->toString()],
            ],
            'required_value' => $id->displayName,
            'command_factory' => static fn () => new DeleteRegistration($id->id->toString()),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.list_users', ['user' => $id->id->toString()])
                ->withFlash('success', 'flash.user_removed');
        }

        return new TwigResponse('admin/user/remove.html.twig', ['form' => $form->createView(), 'user' => $id]);
    }
}
