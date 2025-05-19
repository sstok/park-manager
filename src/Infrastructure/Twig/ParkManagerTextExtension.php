<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use Lifthill\Component\Common\Domain\Model\ByteSize;
use ParkManager\Infrastructure\Translation\Translator;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\TwigFilter;

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
            new TwigFilter('render_byte_size', [$this, 'renderByteSize'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @param array<string, mixed>|string $arguments Can be the locale as a string when $message is a TranslatableInterface
     */
    public function trans(Environment $env, string | \Stringable | TranslatableInterface | null $message, array | string $arguments = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($message === null) {
            return '';
        }

        $this->argumentsTranslator->setEnv($env);

        if ($message instanceof TranslatableInterface) {
            if ($arguments !== [] && ! \is_string($arguments)) {
                throw new \TypeError(\sprintf('Argument 2 passed to "%s()" must be a locale passed as a string when the message is a "%s", "%s" given.', __METHOD__, TranslatableInterface::class, get_debug_type($arguments)));
            }

            return $message->trans($this->argumentsTranslator, $locale ?? (\is_string($arguments) ? $arguments : null));
        }

        if (! \is_array($arguments)) {
            throw new \TypeError(\sprintf('Unless the message is a "%s", argument 2 passed to "%s()" must be an array of parameters, "%s" given.', TranslatableInterface::class, __METHOD__, get_debug_type($arguments)));
        }

        $value = $this->argumentsTranslator->trans((string) $message, $arguments, $domain, $locale);
        $this->argumentsTranslator->setEnv(null);

        return $value;
    }

    public function renderByteSize(ByteSize $value, ?string $locale = null): string
    {
        return $value->trans($this->translator, $locale);
    }

    public function wordwrap(Environment $env, string | \Stringable $text, int $width = 75, string $break = "\n", bool $cut = false, bool $escape = true): string
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
