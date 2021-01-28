<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use ParkManager\UI\Web\Response\TwigResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ListUsersAction
{
    /**
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     *
     * @Route(
     *     path="/users",
     *     methods={"GET", "HEAD"},
     *     name="park_manager.admin.list_users"
     * )
     */
    public function __invoke(Request $request): Response
    {
        return new TwigResponse('admin/user/list.html.twig');
    }
}
