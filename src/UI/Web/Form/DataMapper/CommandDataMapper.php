<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataMapper;

use Symfony\Component\Form\DataAccessorInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

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
    private DataMapperInterface $wrappedDataMapper;
    private DataAccessorInterface $dataAccessor;

    public function __construct(DataMapperInterface $wrappedDataMapper, DataAccessorInterface $dataAccessor)
    {
        $this->wrappedDataMapper = $wrappedDataMapper;
        $this->dataAccessor = $dataAccessor;
    }

    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        if (! \is_array($viewData) || ! \array_key_exists('model', $viewData) || ! \array_key_exists('fields', $viewData)) {
            throw new UnexpectedTypeException($viewData, 'array with keys "model" and "fields"');
        }

        $this->wrappedDataMapper->mapDataToForms($viewData['model'], $forms);
    }

    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        if (! \is_array($viewData) || ! \array_key_exists('model', $viewData) || ! \array_key_exists('fields', $viewData)) {
            throw new UnexpectedTypeException($viewData, 'array with keys "model" and "fields"');
        }

        if ($viewData['model'] === null) {
            return;
        }

        // Data from the forms is mapped directly as the form children names are expected (*NOT* the property-name).

        foreach ($forms as $form) {
            $config = $form->getConfig();

            // Write-back is disabled if the form is not synchronized (transformation failed),
            // if the form was not submitted and if the form is disabled (modification not allowed)
            if ($config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && ! $form->isDisabled()) {
                $propertyValue = $form->getData();
                $modelValue = $this->dataAccessor->getValue($viewData['model'], $form);

                $viewData['fields'][$form->getName()] = $propertyValue;

                // If the field is of type DateTimeInterface and the data is the same skip the update to
                // keep the original object hash
                if ($propertyValue instanceof \DateTimeInterface && $propertyValue == $modelValue) {
                    continue;
                }

                if ($propertyValue !== $modelValue) {
                    $viewData['changed'][$form->getName()] = $propertyValue;
                }
            }
        }
    }
}
