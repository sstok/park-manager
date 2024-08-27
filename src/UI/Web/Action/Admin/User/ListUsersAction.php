<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use Lifthill\Bridge\Doctrine\OrmSearchableResultSet;
use Lifthill\Bridge\Web\Pagerfanta\ResultSetAdapter;
use Lifthill\Component\Datagrid\DatagridFactory;
use Pagerfanta\Pagerfanta;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListUsersAction extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/users', name: 'park_manager.admin.list_users')]
    public function __invoke(Request $request, DatagridFactory $datagridFactory, UserRepository $userRepository): Response
    {
        /** @var OrmSearchableResultSet<User> $users */
        $users = $userRepository->all();
        $users->setSearchField('@id');
        $users->setSearchField('@displayName');
        $users->setSearchField('@email', 'email.address', type: 'text');

        $users->setSearchField('id');
        $users->setSearchField('displayName');
        $users->setSearchField('email', 'email.address', type: 'text');

        $datagrid = $datagridFactory->createDatagridBuilder(true)
            ->add('id', options: ['sortable' => true, 'default_hidden' => true, 'search_type' => TextType::class])
            ->add('displayName', options: ['sortable' => true, 'search_type' => TextType::class])
            ->add('email', options: ['sortable' => true, 'search_type' => TextType::class])

            ->limits(default: 10)
            ->getDatagrid($users)
        ;

        $datagrid->handleRequest($request);

        if ($datagrid->isChanged()) {
            return $this->redirectToRoute('park_manager.admin.list_users', [$datagrid->getName() => $datagrid->getQueryArguments()]);
        }

//        $pagerfanta = new Pagerfanta(new ResultSetAdapter($users));
//        $pagerfanta->setNormalizeOutOfRangePages(true);
//        $pagerfanta->setMaxPerPage(10);
//
//        $pagerfanta->setCurrentPage($request->query->getInt('page', 1));

        return $this->render('admin/user/list.html.twig', [/*'users' => $pagerfanta, */'datagrid' => $datagrid->createView()]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [UserRepository::class, DatagridFactory::class];
    }
}
