<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\RouteAutofillBundle;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * AutoFilledUrlGenerator decorates around an UrlGenerator,
 * trying to automatically fill missing (required) parameters.
 *
 * For this generator to work the following must be true:
 *
 * * The route must be configured with the `autofill_variables` option set to true.
 *
 * * The route is navigated as a tree, were each deeper
 *   branch wants to navigate to a leave of it's own branch or parent:
 *
 *   {account}/
 *      hosts/{id} -- Can redirect back to `{account}/`
 *      hosts/{id}/edit -- Can redirect back to `{account}/` or `hosts/{id}/`
 *
 *   The hosts `{id}` is part of the `{account}` and therefor the account
 *   can be automatically filled if missing, but only given the current url
 *   is part of the prefix (`{account}/`) and the target route is part of
 *   this prefix -- a different prefix could produce unexpected results.
 *
 * Caution:
 *
 * > It's advised to always provide the value of a leave route to prevent
 * > mistakenly targeting an item of a different parent branch.
 * >
 * > Either `ftp-users/{id}/` would use the id of the current host.
 *
 * Tip: This generator is best used with simple operations like
 * a redirect or single navigation item. _Using this generator
 * with many url generations like a menu, list or grid might
 * produce a noticeable slowdown._
 *
 * @see \ParkManager\Bundle\RouteAutofillBundle\Response\RouteRedirectResponse
 */
final class AutoFilledUrlGenerator implements UrlGeneratorInterface
{
    private $urlGenerator;
    private $autoFillMapping;
    private $requestStack;
    private $context;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack, MappingFileLoader $autoFillMapping = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->autoFillMapping = $autoFillMapping ?? MappingFileLoader::fromArray([]);
        $this->requestStack = $requestStack;
    }

    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    public function getContext(): ?RequestContext
    {
        return $this->context;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        $mapping = $this->autoFillMapping->all();

        if (isset($mapping[$name])) {
            $parameters = $this->fillMissingParameters(
                $mapping[$name],
                $parameters,
                $this->requestStack->getCurrentRequest()->attributes
            );
        }

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    private function fillMissingParameters(array $mapping, array $parameters, ParameterBag $attributes): array
    {
        foreach ($mapping as $name => $v) {
            if (isset($parameters[$name]) || !$attributes->has($name)) {
                continue;
            }

            $parameters[$name] = $attributes->get($name);
        }

        return $parameters;
    }
}
