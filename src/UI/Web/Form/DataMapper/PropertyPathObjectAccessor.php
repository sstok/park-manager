<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataMapper;

use Symfony\Component\Form\DataAccessorInterface;
use Symfony\Component\Form\Exception\AccessException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Maps arrays/objects to/from forms using property paths.
 *
 * This is a modified version of the Symfony Form PropertyPathAccessor
 * to allow handling non-array access to objects.
 *
 * @see \Symfony\Component\Form\Extension\Core\DataAccessor\PropertyPathAccessor
 *
 * @license MIT
 */
final class PropertyPathObjectAccessor implements DataAccessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function getValue($viewData, FormInterface $form)
    {
        $propertyPath = $form->getPropertyPath();

        if ($propertyPath === null) {
            throw new AccessException('Unable to read from the given form data as no property path is defined.');
        }

        if (\is_object($viewData) && $propertyPath->isIndex(0)) {
            $propertyPath = $propertyPath->getElement(0);
        } elseif (\is_array($viewData) && $propertyPath->isProperty(0)) {
            $propertyPath = '[' . $propertyPath->getElement(0) . ']';
        }

        return $this->getPropertyValue($viewData, $propertyPath);
    }

    public function setValue(&$viewData, $value, FormInterface $form): void
    {
        // No-op. The CommandDataMapper maps to fields directly.
    }

    private function getPropertyValue(mixed $data, string | PropertyPathInterface $propertyPath)
    {
        try {
            return $this->propertyAccessor->getValue($data, $propertyPath);
        } catch (UninitializedPropertyException $e) {
            throw $e;
        } catch (AccessException) {
            return null;
        }
    }

    public function isReadable($viewData, FormInterface $form): bool
    {
        return $form->getPropertyPath() !== null;
    }

    public function isWritable($viewData, FormInterface $form): bool
    {
        return $form->getPropertyPath() !== null;
    }
}
