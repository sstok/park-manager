<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Test\Domain\Repository;

use ParkManager\Bundle\CoreBundle\Model\Administrator\Administrator;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorRepository;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Event\AdministratorPasswordResetWasRequested;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\AdministratorNotFound;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\PasswordResetConfirmationRejected;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Test\Domain\MockRepository;

final class AdministratorRepositoryMock implements AdministratorRepository
{
    use MockRepository;

    public const USER_ID1 = '126fb452-0a96-11e9-abf1-acbc32b58315';

    protected function getFieldsIndexMapping(): array
    {
        return [
            'email' => static function (Administrator $client) {
                return $client->getEmailAddress()->canonical();
            },
        ];
    }

    protected function getEventsIndexMapping(): array
    {
        return [
            AdministratorPasswordResetWasRequested::class => static function (AdministratorPasswordResetWasRequested $e) {
                return $e->getToken()->selector();
            },
        ];
    }

    protected function throwOnNotFound($key): void
    {
        throw new AdministratorNotFound((string) $key);
    }

    public static function createAdministrator($email = 'janE@example.com', $id = self::USER_ID1): Administrator
    {
        return Administrator::register(AdministratorId::fromString($id), new EmailAddress($email), 'J', 'nope');
    }

    public function get(AdministratorId $id): Administrator
    {
        return $this->mockDoGetById($id);
    }

    public function getByEmail(EmailAddress $email): Administrator
    {
        return $this->mockDoGetByField('email', $email->canonical());
    }

    public function getByPasswordResetToken(string $selector): Administrator
    {
        try {
            return $this->mockDoGetByEvent(AdministratorPasswordResetWasRequested::class, $selector);
        } catch (AdministratorNotFound $e) {
            throw new PasswordResetConfirmationRejected();
        }
    }

    public function save(Administrator $administrator): void
    {
        $this->mockDoSave($administrator);
    }

    public function remove(Administrator $administrator): void
    {
        $this->mockDoRemove($administrator);
    }
}
