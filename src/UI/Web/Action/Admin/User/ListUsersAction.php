<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use Lifthill\Bridge\Doctrine\OrmSearchableResultSet;
use Lifthill\Component\Common\Domain\ResultSet;
use Lifthill\Component\Datagrid\Action\FormAction;
use Lifthill\Component\Datagrid\DatagridAction;
use Lifthill\Component\Datagrid\DatagridFactory;
use Lifthill\Component\Datagrid\Extension\Core\Type\DateTimeType;
use ParkManager\Application\Command\User\RequestPasswordReset;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;
use ParkManager\Infrastructure\Search\Doctrine\UserStatusConversion;
use ParkManager\UI\Web\Form\Type\User\Admin\DatagridAction\AssignUserSecurityLevelActionForm;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Uuid;

final class ListUsersAction extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/users', name: 'park_manager.admin.list_users')]
    public function __invoke(Request $request, DatagridFactory $datagridFactory, UserRepository $userRepository): Response
    {
        /** @var OrmSearchableResultSet<User> $users */
        $users = $userRepository->all();
        $users->setSearchField('id', withOrdering: true);
        $users->setSearchField('displayName', 'displayName', withOrdering: true);
        $users->setSearchField('email', 'email.address', type: 'text', withOrdering: true);
        $users->setSearchField('emailH', 'email.canonical', type: 'text');
        $users->setSearchField('registeredAt', withOrdering: true);
        $users->setSearchField('status', 'id');

        $users->setSearchField('postalCode');

        // $users->setSearchField('role', 'roles');

        $datagrid = $datagridFactory->createDatagridBuilder()
            ->add('displayName', options: [
                'label' => 'label.display_name',
                'search_type' => TextType::class,
                'sortable' => true,
            ])
            ->add('registeredAt', DateTimeType::class, options: [
                'label' => 'label.registered_on',
                'time_format' => \IntlDateFormatter::SHORT,
                'sortable' => true,
            ])
            ->add('status', options: [
                'label' => 'label.status',
                'data_provider' => false,
                'search_type' => ChoiceType::class,
                'search_options' => [
                    'doctrine_orm_conversion' => new UserStatusConversion(),
                    'choices' => [
                        'active' => 'active',
                        'password-expired' => 'password-expired',
                        'email-change-pending' => 'email-change-pending',
                    ],
                ],
            ])
            ->add('role', options: [
                'label' => 'label.role',
                'data_provider' => false,
            ])
            ->add('show', options: [
                'label' => 'label.show',
                'label_attr' => ['class' => 'sr-only'],
                'data_provider' => 'id',
            ])
            ->searchField('id', options: ['constraints' => new Uuid(strict: false)])
            ->searchField('email', options: ['constraints' => new Email()])
            ->searchField('@id')
            ->searchField('@email')
            ->searchField('postalCode', options: ['blind_index' => 'postal_code_hash'])
            ->searchField('emailH', options: ['blind_index' => 'email_hash'])

            ->searchOptions(maxValues: 1, maxGroups: 1, maxNestingLevel: 1)
            ->limits([10, 20, 30, 50, 100], default: 20)

            ->actions([
                'RequestNewPassword' => new DatagridAction(
                    static fn (User $user) => new RequestPasswordReset($user->email->toString()),
                    'Reset password',
                    static fn (ResultSet $resultSet) => \sprintf('Password reset requests where send for %d users', $resultSet->count()),
                ),

                'Test form' => new FormAction(
                    static fn (ResultSet $resultSet) => null,
                    AssignUserSecurityLevelActionForm::class,
                ),
            ])

            ->getDatagrid($users);

        $datagrid->handleRequest($request);

        if ($datagrid->isChanged()) {
            return $this->redirectToRoute('park_manager.admin.list_users', [$datagrid->getName() => $datagrid->getQueryArguments()]);
        }

        return $this->render('admin/user/list.html.twig', ['datagrid' => $datagrid->createView()]);
    }
}
