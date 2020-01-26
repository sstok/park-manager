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
 */
final class CommandDataMapper implements DataMapperInterface
{
    /** @var DataMapperInterface */
    private $wrappedDataMapper;

    public function __construct(DataMapperInterface $wrappedDataMapper)
    {
        $this->wrappedDataMapper = $wrappedDataMapper;
    }

    public function mapDataToForms($data, iterable $forms): void
    {
        if (! \is_array($data) || ! \array_key_exists('model', $data) || ! \array_key_exists('fields', $data)) {
            throw new UnexpectedTypeException($data, 'array with keys "model" and "fields"');
        }

        $this->wrappedDataMapper->mapDataToForms($data['model'], $forms);
    }

    public function mapFormsToData(iterable $forms, &$data): void
    {
        if (! \is_array($data) || ! \array_key_exists('model', $data) || ! \array_key_exists('fields', $data)) {
            throw new UnexpectedTypeException($data, 'array with keys "model" and "fields"');
        }

        $this->wrappedDataMapper->mapFormsToData($forms, $data['fields']);
    }
}
