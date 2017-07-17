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

namespace ParkManager\Component\Model;

use Assert\Assertion;
use Prooph\Common\Messaging\DomainMessage;

/**
 * A ChangeEvent occurs when something changed within
 * the Domain. That other systems are interested in.
 *
 * Note: The Prooph DomainMessage is an implementation.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class DomainEvent extends DomainMessage
{
    /**
     * @var array
     */
    protected $payload = [];

    public static function occur(string $entityId, array $payload = []): self
    {
        return new static($entityId, $payload);
    }

    public function messageType(): string
    {
        return self::TYPE_EVENT;
    }

    public function entityId(): string
    {
        return $this->metadata['_entity_id'];
    }

    /**
     * Return message payload as array.
     *
     * The payload should only contain scalar types and sub arrays.
     * The payload is normally passed to json_encode to persist the message or
     * push it into a message queue.
     */
    public function payload(): array
    {
        return $this->payload;
    }

    protected function __construct(string $entityId, array $payload, array $metadata = [])
    {
        $this->metadata = $metadata;
        $this->setEntityId($entityId);
        $this->setPayload($payload);
        $this->init();
    }

    protected function setEntityId(string $entityId): void
    {
        Assertion::notEmpty($entityId);

        $this->metadata['_entity_id'] = $entityId;
    }

    /**
     * This method is called when a message is instantiated using fromArray().
     *
     * @param array $payload
     */
    protected function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}
