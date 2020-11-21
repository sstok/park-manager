<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PEMFileType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this);
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    public function transform($value)
    {
        return null; // Empty by default
    }

    public function reverseTransform($value)
    {
        /** @var UploadedFile|null $file */
        $file = $value['file'];

        // Let the validator handle this case. No more than 2 MiB
        if ($file === null || ! $file->isValid() || ! $file->isReadable() || $file->getSize() > (1000 * 1000 * 2)) {
            return null;
        }
    }
}
