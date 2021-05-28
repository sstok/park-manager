<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\ByteSize;

/**
 * @ORM\Embeddable
 */
final class DBConstraints
{
    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="byte_size")
     */
    public ByteSize $providedStorageSize;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="integer")
     */
    public int $maximumAmountPerType = -1;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="boolean")
     */
    public bool $enabledPgsql = true;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="boolean")
     */
    public bool $enabledMysql = true;

    /**
     * Change Tracking.
     *
     * [name] => old-value
     *
     * @var array<string, mixed>
     */
    public array $changes = [];

    public function __construct(array $fields = [])
    {
        $this->providedStorageSize = ByteSize::inf();

        foreach ($fields as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    public function setProvidedStorageSize(ByteSize $value): self
    {
        if ($this->providedStorageSize->equals($value)) {
            return $this;
        }

        $self = clone $this;
        $self->providedStorageSize = $value;
        $self->changes['providedStorageSize'] = $this->providedStorageSize;

        return $self;
    }

    public function setMaximumAmountPerType(int $maximumAmountPerType): self
    {
        if ($this->maximumAmountPerType === $maximumAmountPerType) {
            return $this;
        }

        $self = clone $this;
        $self->maximumAmountPerType = $maximumAmountPerType;
        $self->changes['maximumAmountPerType'] = $this->maximumAmountPerType;

        return $self;
    }

    public function enablePgsql(): self
    {
        if ($this->enabledPgsql) {
            return $this;
        }

        $self = clone $this;
        $self->enabledPgsql = true;
        $self->changes['enabledPgsql'] = false;

        return $self;
    }

    public function disablePgsql(): self
    {
        if (! $this->enabledPgsql) {
            return $this;
        }

        $self = clone $this;
        $self->enabledPgsql = false;
        $self->changes['enabledPgsql'] = true;

        return $self;
    }

    public function enableMysql(): self
    {
        if ($this->enabledMysql) {
            return $this;
        }

        $self = clone $this;
        $self->enabledMysql = true;
        $self->changes['enabledMysql'] = false;

        return $self;
    }

    public function disableMysql(): self
    {
        if (! $this->enabledMysql) {
            return $this;
        }

        $self = clone $this;
        $self->enabledMysql = false;
        $self->changes['enabledMysql'] = true;

        return $self;
    }

    public function mergeFrom(self $other): self
    {
        return $this
            ->setProvidedStorageSize($other->providedStorageSize)
            ->setMaximumAmountPerType($other->maximumAmountPerType)
            ->{$other->enabledPgsql ? 'enablePgsql' : 'disablePgsql' }()
            ->{$other->enabledMysql ? 'enableMysql' : 'disableMysql'}();
    }

    public function equals(self $other): bool
    {
        if ($this === $other) {
            return true;
        }

        if (! $this->providedStorageSize->equals($other->providedStorageSize)) {
            return false;
        }

        foreach (['maximumAmountPerType', 'enabledPgsql', 'enabledMysql'] as $field) {
            if ($this->{$field} !== $other->{$field}) {
                return false;
            }
        }

        return true;
    }
}
