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
final class EmailConstraints
{
    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="byte_size")
     */
    public ByteSize $maxStorageSize;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="integer")
     */
    public int $maximumMailboxCount = -1;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="integer")
     */
    public int $maximumForwardCount = -1;

    /**
     * READ-ONLY.
     *
     * Given this value is 0 the actual constraint of mailboxes
     * _and_ forwards is controlled by separate fields.
     *
     * If this value is *higher* than 0 the maximum number of mailboxes
     * and forwards is seen as a whole, controlled by this value.
     *
     * If this value is -1, there is no limit on mailboxes and forwards.
     *
     * @ORM\Column(type="integer")
     */
    public int $maximumAddressCount = 0;

    /**
     * READ-ONLY.
     *
     * Allow to limit how many mailboxes are covered by an active spam filter,
     * this is mainly usable for integration with 3rd party filters.
     *
     * @ORM\Column(type="integer")
     */
    public int $spamFilterCount = -1;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(type="integer")
     */
    public int $mailListCount = 0;

    /**
     * Change Tracking.
     *
     * [name] => old-value
     *
     * @var array<string,mixed>
     */
    public array $changes = [];

    public function __construct(array $fields = [])
    {
        $this->maxStorageSize = ByteSize::inf();

        foreach ($fields as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    public function setMaxStorageSize(ByteSize $value): self
    {
        if ($this->maxStorageSize->equals($value)) {
            return $this;
        }

        $self = clone $this;
        $self->maxStorageSize = $value;
        $self->changes['maxStorageSize'] = $this->maxStorageSize;

        return $self;
    }

    public function setMaximumMailboxCount(int $value): self
    {
        if ($this->maximumMailboxCount === $value) {
            return $this;
        }

        $self = clone $this;
        $self->maximumMailboxCount = $value;
        $self->changes['maximumMailboxCount'] = $this->maximumMailboxCount;

        return $self;
    }

    public function setMaximumForwardCount(int $value): self
    {
        if ($this->maximumForwardCount === $value) {
            return $this;
        }

        $self = clone $this;
        $self->maximumForwardCount = $value;
        $self->changes['maximumForwardCount'] = $this->maximumForwardCount;

        return $self;
    }

    public function setMaximumAddressCount(int $value): self
    {
        if ($this->maximumAddressCount === $value) {
            return $this;
        }

        $self = clone $this;
        $self->maximumAddressCount = $value;
        $self->changes['maximumAddressCount'] = $this->maximumAddressCount;

        return $self;
    }

    public function setSpamFilterCount(int $value): self
    {
        if ($this->spamFilterCount === $value) {
            return $this;
        }

        $self = clone $this;
        $self->spamFilterCount = $value;
        $self->changes['spamFilterCount'] = $this->spamFilterCount;

        return $self;
    }

    public function setMailListCount(int $value): self
    {
        if ($this->mailListCount === $value) {
            return $this;
        }

        $self = clone $this;
        $self->mailListCount = $value;
        $self->changes['mailListCount'] = $this->mailListCount;

        return $self;
    }

    public function equals(self $other): bool
    {
        if ($this === $other) {
            return true;
        }

        if (! $this->maxStorageSize->equals($other->maxStorageSize)) {
            return false;
        }

        foreach (['maximumMailboxCount', 'maximumForwardCount', 'maximumAddressCount', 'spamFilterCount', 'mailListCount'] as $field) {
            if ($this->{$field} !== $other->{$field}) {
                return false;
            }
        }

        return true;
    }

    public function allowsNewMailbox(int $currentMailboxes, int $currentForwards, int $count = 1): bool
    {
        if ($this->maximumAddressCount > 0) {
            return $currentMailboxes + $currentForwards + $count <= $this->maximumAddressCount;
        }

        if ($this->maximumAddressCount === -1 || $this->maximumMailboxCount === -1) {
            return true;
        }

        return $currentMailboxes + $count <= $this->maximumMailboxCount;
    }

    public function allowsNewForward(int $currentForwards, int $currentMailboxes, int $count = 1): bool
    {
        if ($this->maximumAddressCount > 0) {
            return $currentForwards + $currentMailboxes + $count <= $this->maximumAddressCount;
        }

        if ($this->maximumAddressCount === -1 || $this->maximumForwardCount === -1) {
            return true;
        }

        return $currentForwards + $count <= $this->maximumForwardCount;
    }
}
