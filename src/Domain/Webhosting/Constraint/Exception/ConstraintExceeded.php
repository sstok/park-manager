<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint\Exception;

use Exception;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class ConstraintExceeded extends Exception implements TranslatableException
{
    private string $transId;

    /** @var array<string, int|string|ByteSize> */
    private array $transArgs;

    /**
     * @param array<string, int|string|ByteSize> $transArgs
     */
    private function __construct(string $message, array $transArgs = [])
    {
        $message = 'space_constraint_exceeded.' . $message;

        parent::__construct($message);

        $this->transId = $message;
        $this->transArgs = $transArgs;
    }

    public static function emailAddressesCount(int $maximum, int $newAmount): self
    {
        return new self('email_address_count', ['maximum' => $maximum, 'new_amount' => $newAmount]);
    }

    public static function mailboxCount(int $maximum, int $newAmount): self
    {
        return new self('mailbox_count', ['maximum' => $maximum, 'new_amount' => $newAmount]);
    }

    public static function mailboxStorageSizeRange(EmailAddress $address, ByteSize $requested, ByteSize $minimum, ByteSize $maximum): self
    {
        if ($maximum->value === 0) {
            return new self('mailbox_storage_no_space_left', [
                'address' => $address->toString(),
                'requested' => $requested->format(),
            ]);
        }

        return new self('mailbox_storage_size_range', [
            'address' => $address->toString(),
            'requested' => $requested->format(),
            'minimum' => $minimum->format(),
            'maximum' => $maximum->format(),
        ]);
    }

    public static function mailboxStorageResizeRange(EmailAddress $address, ByteSize $requested, ByteSize $minimum, ByteSize $maximum): self
    {
        if ($maximum->value === 0) {
            return new self('mailbox_storage_resize_no_space_left', [
                'address' => $address->toString(),
                'requested' => $requested->format(),
            ]);
        }

        return new self('mailbox_storage_resize_range', [
            'address' => $address->toString(),
            'requested' => $requested->format(),
            'minimum' => $minimum->format(),
            'maximum' => $maximum->format(),
        ]);
    }

    public static function diskStorageSizeRange(SpaceId $id, ByteSize $requested, ByteSize $minimum, ByteSize $maximum): self
    {
        return new self('disk_storage_size_range', [
            'id' => $id->toString(),
            'requested' => $requested->format(),
            'minimum' => $minimum->format(),
            'maximum' => $maximum->format(),
        ]);
    }

    public static function emailForwardCount(int $maximum, int $newAmount): self
    {
        return new self('email_forward', ['maximum' => $maximum, 'new_amount' => $newAmount]);
    }

    public function getTranslatorId(): TranslatableMessage
    {
        return new TranslatableMessage($this->transId, $this->transArgs, 'validators');
    }

    public function getTranslationArgs(): array
    {
        return $this->transArgs;
    }
}
