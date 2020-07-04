<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\ArgumentResolver;

use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class SplitTokenResolver implements ArgumentValueResolverInterface
{
    private SplitTokenFactory $splitTokenFactory;

    public function __construct(SplitTokenFactory $splitTokenFactory)
    {
        $this->splitTokenFactory = $splitTokenFactory;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->isVariadic()) {
            return false;
        }

        return $argument->getType() === SplitToken::class;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->splitTokenFactory->fromString($request->attributes->get($argument->getName()));
    }
}
