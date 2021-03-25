<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\Domain\User\User;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ShowUserAction
{
    /**
     * @Route(
     *     path="/user/{user}/show",
     *     methods={"GET", "HEAD"},
     *     name="park_manager.admin.show_user"
     * )
     */
    public function __invoke(Request $request, User $user): Response
    {
        return new TwigResponse('admin/user/show.html.twig', ['user' => $user]);
    }
}
