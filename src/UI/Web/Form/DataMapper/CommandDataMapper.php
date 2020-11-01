<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataMapper;

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

    public function __construct(DataMapperInterface $wrappedDataMapper)
    {
        $this->wrappedDataMapper = $wrappedDataMapper;
    }

    public function mapDataToForms($viewData, iterable $forms): void
    {
        if (! \is_array($viewData) || ! \array_key_exists('model', $viewData) || ! \array_key_exists('fields', $viewData)) {
            throw new UnexpectedTypeException($viewData, 'array with keys "model" and "fields"');
        }

        $this->wrappedDataMapper->mapDataToForms($viewData['model'], $forms);
    }

    public function mapFormsToData(iterable $forms, &$viewData): void
    {
        if (! \is_array($viewData) || ! \array_key_exists('model', $viewData) || ! \array_key_exists('fields', $viewData)) {
            throw new UnexpectedTypeException($viewData, 'array with keys "model" and "fields"');
        }

        $this->wrappedDataMapper->mapFormsToData($forms, $viewData['fields']);
    }
}
