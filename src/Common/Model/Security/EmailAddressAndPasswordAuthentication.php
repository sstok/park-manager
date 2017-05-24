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

namespace ParkManager\Common\Model\Security;

use ParkManager\Common\Model\Assertion;
use ParkManager\Common\Model\EmailAddress;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class EmailAddressAndPasswordAuthentication implements AuthenticationInfo
{
    private $emailAddress;
    private $password;

    public function __construct(EmailAddress $emailAddress, string $password)
    {
        $this->emailAddress = $emailAddress;
        $this->password = $password;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function setEmailAddress(EmailAddress $emailAddress): EmailAddressAndPasswordAuthentication
    {
        $newModel = clone $this;
        $newModel->emailAddress = $emailAddress;

        return $newModel;
    }

    public function setPassword(string $password): EmailAddressAndPasswordAuthentication
    {
        $newModel = clone $this;
        $newModel->password = $password;

        return $newModel;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(AuthenticationInfo $authentication): bool
    {
        if (!$authentication instanceof self) {
            return false;
        }

        if ($this->password !== $authentication->password) {
            return false;
        }

        if ((string) $this->emailAddress !== (string) $authentication->emailAddress) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return ['email' => $this->emailAddress->toString(), 'password' => $this->password];
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $information): EmailAddressAndPasswordAuthentication
    {
        Assertion::keyIsset($information, 'email');
        Assertion::keyIsset($information, 'password');

        return new self(new EmailAddress($information['email']), $information['password']);
    }
}
