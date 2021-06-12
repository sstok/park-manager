<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure;

use Carbon\CarbonInterval;
use DateInterval;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ObjectTranslatableTranslator implements TranslatorInterface, LocaleAwareInterface
{
    /**
     * @param TranslatorInterface&LocaleAwareInterface $wrappedTranslator
     */
    public function __construct(private TranslatorInterface $wrappedTranslator)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        foreach ($parameters as $key => $value) {
            if ($value instanceof TranslatableInterface) {
                $parameters[$key] = $value->trans($this, $locale);
            } elseif ($value instanceof DateInterval) {
                $parameters[$key] = $this->getDateIntervalString($value, $locale);
            } elseif (\is_object($value) && method_exists($value, '__toString')) {
                $parameters[$key] = $value->__toString();
            }
        }

        return $this->wrappedTranslator->trans($id, $parameters, $domain, $locale);
    }

    private function getDateIntervalString(DateInterval $value, ?string $locale): string
    {
        if (! $value instanceof CarbonInterval) {
            $value = CarbonInterval::instance($value);
        } else {
            $value = clone $value;
        }

        $locale ??= $this->getLocale();
        $value->locale($locale);

        return $value->forHumans();
    }

    public function getLocale(): string
    {
        return $this->wrappedTranslator->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->wrappedTranslator->setLocale($locale);
    }
}
