<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\TwigFilter;

final class ParkManagerExtension extends AbstractExtension
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('trans_safe', [$this, 'trans'], ['needs_environment' => true, 'is_safe' => ['all']]),
        ];
    }

    public function trans(Environment $env, string $message, array $arguments = [], ?string $domain = null, ?string $locale = null, ?int $count = null): string
    {
        if ($count !== null) {
            $arguments['%count%'] = $count;
        }

        foreach ($arguments as $name => $value) {
            $arguments[$name] = twig_escape_filter($env, $value);
        }

        return $this->translator->trans($message, $arguments, $domain, $locale);
    }
}

// Force autoloading of the EscaperExtension as we need the twig_escape_filter() function
\class_exists(EscaperExtension::class);
