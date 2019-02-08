<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\ExpressionLanguage\Expression;

function ref(string $id): ReferenceConfigurator
{
}

function inline(string $class = null): InlineServiceConfigurator
{
}

/**
 * @param ReferenceConfigurator[] $values
 */
function service_locator(array $values): ServiceLocatorArgument
{
}

/**
 * @param ReferenceConfigurator[] $values
 */
function iterator(array $values): IteratorArgument
{
}

function tagged(string $tag): TaggedIteratorArgument
{
}

function expr(string $expression): Expression
{
}
