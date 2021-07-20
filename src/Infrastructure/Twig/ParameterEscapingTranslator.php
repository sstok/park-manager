<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use BadMethodCallException;
use ParkManager\Infrastructure\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\EscaperExtension;

final class ParameterEscapingTranslator implements TranslatorInterface
{
    private ?Environment $env;

    public function __construct(private Translator $wrappedTranslator)
    {
    }

    public function setEnv(?Environment $env): void
    {
        $this->env = $env;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->wrappedTranslator->trans($id, $parameters, $domain, $locale, [$this, 'escape']);
    }

    public function escape(mixed $value): mixed
    {
        if ($this->env === null) {
            throw new BadMethodCallException('setEnv() is expected to be called first.');
        }

        return twig_escape_filter($this->env, $value);
    }

    public function getLocale(): string
    {
        return $this->wrappedTranslator->getLocale();
    }
}

// Force autoloading of the EscaperExtension as we need the twig_escape_filter() function.
// While this class is only used by the ParkManagerTextExtension SA still needs to look for
// this functions existence.
class_exists(EscaperExtension::class);
