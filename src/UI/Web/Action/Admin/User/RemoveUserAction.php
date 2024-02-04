<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\User;

use Lifthill\Bridge\Web\Form\Type\ConfirmationForm;
use ParkManager\Application\Command\User\DeleteRegistration;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\Exception\CannotRemoveActiveUser;
use ParkManager\Domain\User\User;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\UI\Web\Form\RawFormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RemoveUserAction extends AbstractController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/user/{id}/remove', methods: ['GET', 'POST', 'HEAD'], name: 'park_manager.admin.remove_user')]
    public function __invoke(Request $request, User $id, EntityRenderer $entityRenderer): Response
    {
        $form = $this->createForm(ConfirmationForm::class, null, [
            'confirmation_title' => 'user_management.remove.heading',
            'confirmation_message' => 'user_management.remove.confirm_warning',
            'confirmation_label' => 'label.remove',
            'cancel_route' => [
                'name' => 'park_manager.admin.show_user',
                'arguments' => ['user' => $id->id->toString()],
            ],
            'required_value' => $id->displayName,
            'command_factory' => static fn () => new DeleteRegistration($id->id->toString()),
            'exception_mapping' => [
                CannotRemoveActiveUser::class => static function (CannotRemoveActiveUser $exception, TranslatorInterface $translator) use ($entityRenderer): RawFormError {
                    return new RawFormError(
                        $translator->trans(
                            'cannot_remove_active_user',
                            ['entities' => $entityRenderer->listedBySet($exception->entities, ['is_admin' => true])],
                            'validators'
                        ),
                        'cannot_remove_active_user',
                        cause: $exception
                    );
                },
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.user_removed'));

            return $this->redirectToRoute('park_manager.admin.list_users', ['user' => $id->id->toString()]);
        }

        return $this->render('admin/user/remove.html.twig', ['form' => $form, 'user' => $id]);
    }
}
