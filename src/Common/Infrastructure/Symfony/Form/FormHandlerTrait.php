<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Common\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

trait FormHandlerTrait
{
    protected function handleFormResponse(FormInterface $form, \Closure $onFailure, \Closure $onSuccess)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $onSuccess();
            } catch (ValidationException $e) {
                // XXX - ValidationExceptionToFormError($e, $form) service/method, with translation;
                // XXX - The Application Service uses the Validator, so it would make sense
                // to keep violations in a collection and then map those violations to FormError objects.
                // Similar to RollerworksSearch.
                $form
                    ->get('name')
                    ->addError(
                        new FormError(
                            '',
                            $e->getMessageKey(),
                            $e->getMessageParameters(),
                            $e->getMessagePluralization()
                        )
                    );

                return $onFailure();
            }
        }

        // Form was not submitted or was invalid, no need for else as the previous condition
        // will always return.
        return $onFailure();
    }
}
