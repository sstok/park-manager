<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\ArgumentResolver;

use ParkManager\Domain\Exception\InvalidSplitTokenProvided;
use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

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
        yield $this->getFromString($request->attributes->get($argument->getName()));
    }

    /**
     * @throws InvalidSplitTokenProvided
     */
    private function getFromString(string $value): SplitToken
    {
        try {
            return $this->splitTokenFactory->fromString($value);
        } catch (Throwable $e) {
            throw new InvalidSplitTokenProvided('Invalid token', Response::HTTP_BAD_REQUEST, $e);
        }
    }
}
