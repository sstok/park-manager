<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataMapper;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Maps arrays/objects to/from forms using property paths.
 *
 * This is a modified version of the Symfony Form PropertyPathMapper
 * to allow handling non-array access to objects.
 *
 * @see \Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper
 *
 * @license MIT
 */
final class PropertyPathObjectMapper extends PropertyPathMapper
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        parent::__construct($this->propertyAccessor);
    }

    public function mapDataToForms($data, iterable $forms): void
    {
        if ($data !== null && ! \is_array($data) && ! \is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        $empty = $data === null || $data === [];
        $forceOjbAccess = \is_object($data);

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            if ($empty || $propertyPath === null || ! $config->getMapped()) {
                $form->setData($config->getData());
            } else {
                $form->setData($this->getPropertyValue($data, $propertyPath, $forceOjbAccess));
            }
        }
    }

    /**
     * @param array|mixed|object $data
     */
    private function getPropertyValue($data, PropertyPathInterface $propertyPath, bool $forceOjbAccess)
    {
        if ($forceOjbAccess && $propertyPath->isIndex(0)) {
            $propertyPath = new PropertyPath($propertyPath->getElement(0));
        }

        try {
            return $this->propertyAccessor->getValue($data, $propertyPath);
        } catch (UninitializedPropertyException $e) {
            throw $e;
        } catch (AccessException $e) {
            return null;
        }
    }
}
