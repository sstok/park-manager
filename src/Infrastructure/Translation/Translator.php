<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Translation;

use DateTimeInterface;
use ParkManager\Domain\Translation\ParameterValue;
use ParkManager\Domain\Translation\ParameterValueService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This Translator implementation wraps around the actual translator,
 * providing functionality for specific parameter formatting and value escaping.
 */
class Translator implements TranslatorInterface, LocaleAwareInterface, TranslatorBagInterface
{
    /**
     * @param TranslatorInterface&LocaleAwareInterface&TranslatorBagInterface $wrappedTranslator
     */
    public function __construct(private TranslatorInterface $wrappedTranslator, private ContainerInterface $parameterFormatterServices)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null, ?callable $escaper = null): string
    {
        if ($id === null || $id === '') {
            return '';
        }

        $locale ??= $this->getLocale();
        $escaper ??= [static::class, 'escape'];

        foreach ($parameters as $key => $value) {
            // Needs to be kept as-is. The MessageFormatter will handle this.
            if ($value instanceof DateTimeInterface) {
                continue;
            }

            if ($value instanceof ParameterValue) {
                $parameters[$key] = $value->format($locale, $escaper, $this);

                continue;
            }

            if ($value instanceof ParameterValueService) {
                $parameters[$key] = $this->formatParameterService($value, $locale, $escaper);

                continue;
            }

            if ($value instanceof TranslatableInterface) {
                // Note. This value still might need escaping.
                // We can't pass the escaper, use a ParameterValue instead when format is required.
                $parameters[$key] = $value = $value->trans($this, $locale);
            }

            $parameters[$key] = $escaper($value, $locale);
        }

        return $this->wrappedTranslator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Default escaping callable. Converts scalar value to a string.
     *
     * @internal
     */
    public static function escape(mixed $value): string | int | float
    {
        if (\is_float($value) || \is_int($value)) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * @param callable(mixed): string $escaper
     */
    private function formatParameterService(ParameterValueService $value, string $locale, callable $escaper): string
    {
        $service = $this->parameterFormatterServices->get($value::class);
        \assert($service instanceof TranslationParameterFormatter);

        return $service->format($value, $locale, $escaper, $this);
    }

    public function getLocale(): string
    {
        return $this->wrappedTranslator->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->wrappedTranslator->setLocale($locale);
    }

    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        return $this->wrappedTranslator->getCatalogue($locale);
    }

    /**
     * @return array<int, MessageCatalogueInterface>
     */
    public function getCatalogues(): array
    {
        return $this->wrappedTranslator->getCatalogues();
    }
}
