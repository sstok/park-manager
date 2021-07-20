<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use DateTimeInterface;
use ParkManager\Domain\ByteSize;
use ParkManager\Infrastructure\Translation\Translator;
use Stringable;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use TypeError;

final class ParkManagerTextExtension extends AbstractExtension
{
    private ParameterEscapingTranslator $argumentsTranslator;

    public function __construct(
        private Translator $translator
    ) {
        $this->argumentsTranslator = new ParameterEscapingTranslator($translator);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_safe', [$this, 'trans'], ['needs_environment' => true, 'is_safe' => ['all']]),
            new TwigFilter('wordwrap', [$this, 'wordwrap'], ['needs_environment' => true, 'is_safe' => ['all']]),
            new TwigFilter('escape_array', [$this, 'escapeArray'], ['needs_environment' => true, 'is_safe' => ['all']]),
            new TwigFilter('render_byte_size', [$this, 'renderByteSize'], ['is_safe' => ['all']]),
            new TwigFilter('merge_attr_class', [$this, 'mergeAttrClass']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('create_byte_size', [ByteSize::class, 'fromString']),
        ];
    }

    /**
     * @param array<array-key, mixed> $array
     * @param list<string>            $ignore List of keys to ignore (root level only!)
     *
     * @return array<array-key, mixed>
     */
    public function escapeArray(Environment $env, array $array, string $strategy = 'html', array $ignore = []): array
    {
        foreach ($array as $k => $v) {
            if ($v instanceof DateTimeInterface || \in_array($k, $ignore, true)) {
                continue;
            }

            if (\is_array($v)) {
                $array[$k] = $this->escapeArray($env, $v);
            } else {
                $array[$k] = twig_escape_filter($env, $v, $strategy);
            }
        }

        return $array;
    }

    /**
     * @param array<string, mixed>|string $arguments Can be the locale as a string when $message is a TranslatableInterface
     */
    public function trans(Environment $env, TranslatableInterface | Stringable | string | null $message, array | string $arguments = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($message === null) {
            return '';
        }

        $this->argumentsTranslator->setEnv($env);

        if ($message instanceof TranslatableInterface) {
            if ($arguments !== [] && ! \is_string($arguments)) {
                throw new TypeError(sprintf('Argument 2 passed to "%s()" must be a locale passed as a string when the message is a "%s", "%s" given.', __METHOD__, TranslatableInterface::class, get_debug_type($arguments)));
            }

            return $message->trans($this->argumentsTranslator, $locale ?? (\is_string($arguments) ? $arguments : null));
        }

        if (! \is_array($arguments)) {
            throw new TypeError(sprintf('Unless the message is a "%s", argument 2 passed to "%s()" must be an array of parameters, "%s" given.', TranslatableInterface::class, __METHOD__, get_debug_type($arguments)));
        }

        $value = $this->argumentsTranslator->trans((string) $message, $arguments, $domain, $locale);
        $this->argumentsTranslator->setEnv(null);

        return $value;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public function mergeAttrClass(array $attributes, string $class, bool $append = false): array
    {
        if (! isset($attributes['class'])) {
            $attributes['class'] = '';
        }

        if ($append) {
            $attributes['class'] .= ' ' . $class;
        } else {
            $attributes['class'] = $class . ' ' . $attributes['class'];
        }

        $attributes['class'] = trim($attributes['class']);

        return $attributes;
    }

    public function renderByteSize(ByteSize $value, ?string $locale = null): string
    {
        return $value->trans($this->translator, $locale);
    }

    public function wordwrap(Environment $env, string | Stringable $text, int $width = 75, string $break = "\n", bool $cut = false, bool $escape = true): string
    {
        if ($escape) {
            $text = twig_escape_filter($env, (string) $text);
        }

        if (! $text instanceof UnicodeString) {
            $text = new UnicodeString((string) $text);
        }

        return $text->wordwrap($width, $break, $cut)->toString();
    }
}

// Force autoloading of the EscaperExtension as we need the twig_escape_filter() function
class_exists(EscaperExtension::class);
