<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Account\Handler;

use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\WebhostingModule\Application\Account\RegisterWebhostingAccount;
use ParkManager\Module\WebhostingModule\Application\Account\RegisterWebhostingAccountHandler;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountRepository;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainNameRepository;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageRepository;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class RegisterWebhostingAccountHandlerTest extends TestCase
{
    private const OWNER_ID1   = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const PACKAGE_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const ACCOUNT_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ACCOUNT_ID2 = '696d345c-a5e1-11e7-9856-acbc32b58315';

    /** @test */
    public function it_handles_registration_of_account_with_package(): void
    {
        $capabilities         = new Capabilities(new MonthlyTrafficQuota(50));
        $domainName           = new DomainName('example', '.com');
        $webhostingPackage    = WebhostingPackage::create(WebhostingPackageId::fromString(self::PACKAGE_ID1), $capabilities);
        $packageRepository    = $this->createPackageRepository($webhostingPackage);
        $accountRepository    = $this->createAccountRepositoryThatSaves($capabilities, $webhostingPackage);
        $domainNameRepository = $this->createDomainNameRepositoryThatSaves($domainName, self::ACCOUNT_ID1);
        $handler              = new RegisterWebhostingAccountHandler($accountRepository, $packageRepository, $domainNameRepository);

        $handler(
            RegisterWebhostingAccount::withPackage(
                self::ACCOUNT_ID1,
                $domainName,
                self::OWNER_ID1,
                self::PACKAGE_ID1
            )
        );
    }

    /** @test */
    public function it_handles_registration_of_account_with_custom_capabilities(): void
    {
        $capabilities         = new Capabilities(new MonthlyTrafficQuota(50));
        $domainName           = new DomainName('example', '.com');
        $packageRepository    = $this->createNullPackageRepository();
        $accountRepository    = $this->createAccountRepositoryThatSaves($capabilities);
        $domainNameRepository = $this->createDomainNameRepositoryThatSaves($domainName, self::ACCOUNT_ID1);
        $handler              = new RegisterWebhostingAccountHandler($accountRepository, $packageRepository, $domainNameRepository);

        $handler(
            RegisterWebhostingAccount::withCustomCapabilities(
                self::ACCOUNT_ID1,
                new DomainName('example', '.com'),
                self::OWNER_ID1,
                $capabilities
            )
        );
    }

    /** @test */
    public function it_checks_domain_is_not_already_registered(): void
    {
        $domainName           = new DomainName('example', '.com');
        $accountId2           = WebhostingAccountId::fromString(self::ACCOUNT_ID2);
        $packageRepository    = $this->createNullPackageRepository();
        $accountRepository    = $this->createAccountRepositoryWithoutSave();
        $domainNameRepository = $this->createDomainNameRepositoryWithExistingRegistration($domainName, $accountId2);
        $handler              = new RegisterWebhostingAccountHandler($accountRepository, $packageRepository, $domainNameRepository);

        $this->expectException(DomainNameAlreadyInUse::class);
        $this->expectExceptionMessage(DomainNameAlreadyInUse::byAccountId($domainName, $accountId2)->getMessage());

        $handler(
            RegisterWebhostingAccount::withPackage(
                self::ACCOUNT_ID1,
                $domainName,
                self::OWNER_ID1,
                self::PACKAGE_ID1
            )
        );
    }

    private function createAccountRepositoryThatSaves(Capabilities $capabilities, ?WebhostingPackage $package = null, string $id = self::ACCOUNT_ID1, string $owner = self::OWNER_ID1): WebhostingAccountRepository
    {
        $accountRepositoryProphecy = $this->prophesize(WebhostingAccountRepository::class);
        $accountRepositoryProphecy->save(
            Argument::that(
                static function (WebhostingAccount $account) use ($capabilities, $id, $owner, $package) {
                    self::assertEquals(WebhostingAccountId::fromString($id), $account->id());
                    self::assertEquals(OwnerId::fromString($owner), $account->owner());
                    self::assertEquals($capabilities, $account->capabilities());
                    self::assertEquals($package, $account->package());

                    return true;
                }
            )
        )->shouldBeCalled();

        return $accountRepositoryProphecy->reveal();
    }

    private function createAccountRepositoryWithoutSave(): WebhostingAccountRepository
    {
        $accountRepositoryProphecy = $this->prophesize(WebhostingAccountRepository::class);
        $accountRepositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $accountRepositoryProphecy->reveal();
    }

    private function createNullPackageRepository(): WebhostingPackageRepository
    {
        return $this->createMock(WebhostingPackageRepository::class);
    }

    private function createPackageRepository(WebhostingPackage $package): WebhostingPackageRepository
    {
        $packageRepositoryProphecy = $this->prophesize(WebhostingPackageRepository::class);
        $packageRepositoryProphecy->get($package->id())->willReturn($package);

        return $packageRepositoryProphecy->reveal();
    }

    private function createDomainNameRepositoryThatSaves(DomainName $expectedDomain, string $accountId): WebhostingDomainNameRepository
    {
        $domainNameRepositoryProphecy = $this->prophesize(WebhostingDomainNameRepository::class);
        $domainNameRepositoryProphecy->findByFullName($expectedDomain)->willReturn(null);
        $domainNameRepositoryProphecy->save(
            Argument::that(
                static function (WebhostingDomainName $domain) use ($expectedDomain, $accountId) {
                    self::assertEquals($expectedDomain, $domain->domainName());
                    self::assertEquals(WebhostingAccountId::fromString($accountId), $domain->account()->id());

                    return true;
                }
            )
        )->shouldBeCalled();

        return $domainNameRepositoryProphecy->reveal();
    }

    private function createDomainNameRepositoryWithExistingRegistration(DomainName $expectedDomain, WebhostingAccountId $existingAccountId): WebhostingDomainNameRepository
    {
        $existingAccount = $this->createMock(WebhostingAccount::class);
        $existingAccount
            ->expects(self::any())
            ->method('id')
            ->willReturn($existingAccountId);

        $existingDomain = $this->createMock(WebhostingDomainName::class);
        $existingDomain
            ->expects(self::any())
            ->method('account')
            ->willReturn($existingAccount);

        $domainNameRepositoryProphecy = $this->prophesize(WebhostingDomainNameRepository::class);
        $domainNameRepositoryProphecy->findByFullName($expectedDomain)->willReturn($existingDomain);
        $domainNameRepositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $domainNameRepositoryProphecy->reveal();
    }
}
