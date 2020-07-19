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

final class ConstraintExceeded extends Exception implements TranslatableException
{
    private string $transId;
    private array $transArgs;

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
        return new self('mailbox_storage_size_range', [
            'address' => $address->toString(),
            'requested' => $requested->format(),
            'minimum' => $minimum->format(),
            'maximum' => $maximum->format(),
        ]);
    }

    public static function emailForwardCount(int $maximum, int $newAmount): self
    {
        return new self('email_forward', ['maximum' => $maximum, 'new_amount' => $newAmount]);
    }

    public function getTranslatorId(): string
    {
        return $this->transId;
    }

    public function getTranslationArgs(): array
    {
        return $this->transArgs;
    }
}
