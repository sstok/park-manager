<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataMapper;

use DateTimeInterface;
use ParkManager\UI\Web\Form\Model\CommandDto;
use Symfony\Component\Form\DataAccessorInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Traversable;

/**
 * Handles the mapping of a MessageFormType modelData to forms
 * and to the modelData fields collection.
 *
 * The main purpose of this mapper is to protect the entity from being
 * changed by the Form DataMapper. And tracking the actual changes for
 * the command-factory.
 *
 * In short: Child forms receive the "model", but when setting their data,
 * this will be mapped to the "fields", which is then used to construct
 * the Command message.
 *
 * @see \ParkManager\UI\Web\Form\Type\MessageFormType::buildForm
 */
final class CommandDataMapper implements DataMapperInterface
{
    public function __construct(
        private DataMapperInterface $wrappedDataMapper,
        private DataAccessorInterface $dataAccessor
    ) {
    }

    public function mapDataToForms($viewData, Traversable $forms): void
    {
        if (! $viewData instanceof CommandDto) {
            throw new UnexpectedTypeException($viewData, CommandDto::class);
        }

        $this->wrappedDataMapper->mapDataToForms($viewData->model, $forms);
    }

    public function mapFormsToData(Traversable $forms, &$viewData): void
    {
        if (! $viewData instanceof CommandDto) {
            throw new UnexpectedTypeException($viewData, CommandDto::class);
        }

        foreach ($forms as $form) {
            $config = $form->getConfig();

            // Write-back is disabled if the form is not synchronized (transformation failed),
            // if the form was not submitted and if the form is disabled (modification not allowed)
            if ($config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && ! $form->isDisabled()) {
                $fieldValue = $form->getData();

                $viewData->fields[$form->getName()] = $fieldValue;

                if ($viewData->model === null) {
                    continue;
                }

                $modelValue = $this->dataAccessor->getValue($viewData->model, $form);

                if ($fieldValue instanceof DateTimeInterface && $fieldValue == $modelValue) {
                    continue;
                }

                if ($fieldValue !== $modelValue) {
                    $viewData->changes[$form->getName()] = $fieldValue;
                }
            }
        }
    }
}
