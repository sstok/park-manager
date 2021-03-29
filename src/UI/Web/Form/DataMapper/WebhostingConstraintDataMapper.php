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
use Symfony\Component\Form\FormInterface;

final class WebhostingConstraintDataMapper implements DataMapperInterface
{
    private string $constraintClass;

    public function __construct(string $constraintClass)
    {
        $this->constraintClass = $constraintClass;
    }

    /**
     * @param \RecursiveIteratorIterator $forms
     */
    public function mapDataToForms($viewData, iterable $forms): void
    {
        if (! $viewData instanceof $this->constraintClass) {
            throw new UnexpectedTypeException($viewData, $this->constraintClass);
        }

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        foreach ($forms as $name => $form) {
            $form->setData($viewData->{$name});
        }
    }

    /**
     * @param \RecursiveIteratorIterator $forms
     */
    public function mapFormsToData(iterable $forms, &$viewData): void
    {
        if (! $viewData instanceof $this->constraintClass) {
            throw new UnexpectedTypeException($viewData, $this->constraintClass);
        }

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        $fields = [];

        foreach ($forms as $name => $form) {
            $data = $form->getData();

            if ($data !== null) {
                $fields[$name] = $data;
            }
        }

        $className = $this->constraintClass;

        // Merge with the old data to ensure changes are tracked.
        $newConfig = new $className($fields);
        $viewData = $viewData->mergeFrom($newConfig);
    }
}
