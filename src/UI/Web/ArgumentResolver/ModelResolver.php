<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\ArgumentResolver;

use ParkManager\Domain\EmailAddress;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ModelResolver implements ArgumentValueResolverInterface
{
    private ContainerInterface $entitiesRepositories;

    /** @var array<class-string,string> */
    private array $valueObjects;

    /**
     * @param ContainerInterface         $entitiesRepositories [entity-name] => IdClassName
     * @param array<class-string,string> $valueObjectsMapping  [class-name] => factoryMethodName
     */
    public function __construct(ContainerInterface $entitiesRepositories, array $valueObjectsMapping)
    {
        $this->entitiesRepositories = $entitiesRepositories;
        $this->valueObjects = $valueObjectsMapping;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->isVariadic()) {
            return false;
        }

        $type = $argument->getType() ?? '[Null]';

        if ($type === EmailAddress::class) {
            return true;
        }

        if (isset($this->valueObjects[$type])) {
            return true;
        }

        return $this->entitiesRepositories->has($type);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        $default = $argument->hasDefaultValue() ? $argument->getDefaultValue() : null;
        $value = $request->attributes->get($argument->getName(), $default);

        if ($value === null || $type === null) {
            throw new RuntimeException(sprintf('Value/type for argument "%s" cannot be null.', $argument->getName()));
        }

        if ($type === EmailAddress::class) {
            yield new EmailAddress($value);
        } elseif (isset($this->valueObjects[$type])) {
            yield $type::{$this->valueObjects[$type]}($value);
        } else {
            // EntityName + Id = {Space}Id
            yield $this->entitiesRepositories->get($type)->get(($type . 'Id')::fromString($value));
        }
    }
}
