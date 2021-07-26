<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Translation\Formatter;

use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\ParameterValueService;
use ParkManager\Domain\UniqueIdentity;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\Infrastructure\Translation\TranslationParameterFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EntityLinkFormatter implements TranslationParameterFormatter
{
    public function __construct(
        private RepositoryLocator $repositoryLocator,
        private EntityRenderer $entityRenderer
    ) {
    }

    public function format(ParameterValueService $value, string $locale, callable $escaper, TranslatorInterface $translator): string
    {
        \assert($value instanceof EntityLink);

        if ($value->entity instanceof UniqueIdentity) {
            $repository = $this->repositoryLocator->getById($value->entity);
            $entity = $repository->get($value->entity);
        } else {
            $entity = $value->entity;
        }

        return $this->entityRenderer->link($entity, [], $locale);
    }
}
