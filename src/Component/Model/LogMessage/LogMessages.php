<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\Model\LogMessage;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class LogMessages implements \Countable
{
    /**
     * Messages per type.
     *
     * @var array[]
     */
    private $messages = [];

    /**
     * Cached message count.
     *
     * @var int
     */
    private $count = 0;

    public function hasErrors(): bool
    {
        return isset($this->messages['error']);
    }

    public function add(LogMessage $message): void
    {
        $this->messages[$message->type][] = $message;
        ++$this->count;
    }

    /**
     * @return array[]
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function allOf(string $type): array
    {
        return $this->messages[$type] ?? [];
    }

    public function count()
    {
        return $this->count;
    }
}
