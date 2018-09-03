<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Component\ApplicationFoundation\Message;

final class ServiceMessages implements \Countable
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

    public function add(ServiceMessage $message): void
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

    public function clear(): void
    {
        $this->messages = [];
        $this->count = 0;
    }

    public function allOf(string $type): array
    {
        return $this->messages[$type] ?? [];
    }

    public function count(): int
    {
        return $this->count;
    }

    public function merge(self $messages): void
    {
        $this->messages = array_merge_recursive($this->messages, $messages->messages);
        $this->count += $messages->count();
    }
}
