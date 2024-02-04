<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use ParkManager\Infrastructure\Service\EntityRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ParkManagerEntityRenderExtension extends AbstractExtension
{
    public function __construct(private EntityRenderer $entityRenderer) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_entity_short', [$this->entityRenderer, 'short'], ['is_safe' => 'all']),
            new TwigFunction('render_entity_detailed', [$this->entityRenderer, 'detailed'], ['is_safe' => 'all']),
            new TwigFunction('render_entity_link', [$this->entityRenderer, 'link'], ['is_safe' => 'all']),
            new TwigFunction('render_entity_label', [$this->entityRenderer, 'getEntityLabel'], ['is_safe' => 'html']),
        ];
    }
}
