<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\Domain\User\UserId;
use ParkManager\UI\Web\Form\Type\User\RegisterUserForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RegisterUserAction
{
    #[Security("is_granted('ROLE_SUPER_ADMIN')")]
    #[Route(path: '/user/register', methods: ['GET', 'POST', 'HEAD'], name: 'park_manager.admin.register_user')]
    public function __invoke(Request $request, FormFactoryInterface $formFactory): TwigResponse | RouteRedirectResponse
    {
        $userId = UserId::create();

        $form = $formFactory->create(RegisterUserForm::class, null, ['user_id' => $userId]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.show_user', ['user' => $userId->toString()])
                ->withFlash('success', 'flash.user_registered')
            ;
        }

        return new TwigResponse('admin/user/register.html.twig', $form);
    }
}
