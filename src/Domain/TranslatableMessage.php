<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatableMessage implements TranslatableInterface, \Stringable
{
    private string $message;
    /** @var array<string, mixed> */
    private array $parameters;
    private ?string $domain;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(string $message, array $parameters = [], ?string $domain = null)
    {
        $this->message = $message;
        $this->parameters = $parameters;
        $this->domain = $domain;
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $parameters = $this->getParameters();

        foreach ($parameters as $key => $value) {
            if ($value instanceof TranslatableInterface) {
                $parameters[$key] = $value->trans($translator, $locale);
            } elseif (\is_object($value) && method_exists($value, '__toString')) {
                $parameters[$key] = $value->__toString();
            }
        }

        return $translator->trans($this->getMessage(), $parameters, $this->getDomain(), $locale);
    }
}
