<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Mailer;

final class TemplatedEmail extends \Symfony\Bridge\Twig\Mime\TemplatedEmail
{
    private array $originalContext = [];

    public function context(array $context): static
    {
        $this->originalContext = $context;

        return parent::context($context);
    }

    public function getOriginalContext(): array
    {
        return $this->originalContext;
    }

    /**
     * @internal
     */
    public function __serialize(): array
    {
        return [$this->originalContext, parent::__serialize()];
    }

    /**
     * @internal
     */
    public function __unserialize(array $data): void
    {
        [$this->originalContext, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
