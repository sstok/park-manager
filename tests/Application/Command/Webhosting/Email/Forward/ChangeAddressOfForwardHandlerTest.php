<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Email\Forward;

use Lifthill\Component\Common\Domain\Model\ByteSize;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Application\Command\Webhosting\Email\Forward\ChangeAddressOfForward;
use ParkManager\Application\Command\Webhosting\Email\Forward\ChangeAddressOfForwardHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\ForwardRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\MailboxRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ChangeAddressOfForwardHandlerTest extends TestCase
{
    private const DOMAIN_1 = '017d0b56-2d66-c966-b1ec-fc190f8a5c5f';
    private const DOMAIN_2 = '017d19e8-38a0-5ce0-773f-1de5cef69cd0';
    private const FORWARD_ID1 = '017d156d-8241-da3f-4328-158eaa59ec15';

    private SpaceRepositoryMock $spaceRepository;
    private DomainNameRepositoryMock $domainNameRepository;
    private ForwardRepositoryMock $forwardRepository;
    private ChangeAddressOfForwardHandler $handler;

    protected function setUp(): void
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(),
        ]);

        $this->domainNameRepository = new DomainNameRepositoryMock([
            $domainName1 = DomainName::registerForSpace(
                DomainNameId::fromString(self::DOMAIN_1),
                $space1,
                new DomainNamePair('example1', 'com')
            ),
            $domainName2 = DomainName::registerSecondaryForSpace(
                DomainNameId::fromString(self::DOMAIN_2),
                $space1,
                new DomainNamePair('example1', 'net')
            ),
        ]);
        $this->spaceRepository->save($space1);
        $this->spaceRepository->resetRecordingState();

        $mailboxRepository = new MailboxRepositoryMock([
            new Mailbox(MailboxId::fromString('017d157a-4ca8-4275-01af-1e399720504a'), $space1, 'timith', $domainName1, ByteSize::inf(), 'nope'),
            new Mailbox(MailboxId::fromString('017d1ec6-af2f-9e42-9609-3a1c1e751cba'), $space1, 'timith', $domainName2, ByteSize::inf(), 'nope'),
        ]);
        $this->forwardRepository = new ForwardRepositoryMock([
            Forward::toScript(ForwardId::fromString(self::FORWARD_ID1), $space1, 'administration', $domainName1, 'script.php'),
        ]);

        $this->handler = new ChangeAddressOfForwardHandler(
            $this->forwardRepository,
            $this->domainNameRepository,
            $mailboxRepository,
        );
    }

    /** @test */
    public function it_does_nothing_when_same(): void
    {
        $this->handler->__invoke(
            new ChangeAddressOfForward(
                ForwardId::fromString(self::FORWARD_ID1),
                'administration',
                DomainNameId::fromString(self::DOMAIN_1),
            )
        );

        $this->forwardRepository->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_does_nothing_when_same_without_domain_name(): void
    {
        $this->handler->__invoke(
            new ChangeAddressOfForward(
                ForwardId::fromString(self::FORWARD_ID1),
                'administration',
            )
        );

        $this->forwardRepository->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_changes_address(): void
    {
        $this->handler->__invoke(
            new ChangeAddressOfForward(
                $id = ForwardId::fromString(self::FORWARD_ID1),
                $address = 'admin',
                $domainName = DomainNameId::fromString(self::DOMAIN_1),
            )
        );

        $this->forwardRepository->assertEntitiesCountWasSaved(1);
        $this->forwardRepository->assertEntityWasSavedThat($id, function (Forward $mailbox) use ($address, $domainName): bool {
            self::assertSame($address, $mailbox->address);
            self::assertSame($this->domainNameRepository->get($domainName), $mailbox->domainName);

            // Should not be changed
            self::assertSame('script:script.php', $mailbox->destination);

            return true;
        });
    }

    /** @test */
    public function it_changes_address_without_domain_name(): void
    {
        $this->handler->__invoke(
            new ChangeAddressOfForward(
                $id = ForwardId::fromString(self::FORWARD_ID1),
                $address = 'admin',
            )
        );

        $this->forwardRepository->assertEntitiesCountWasSaved(1);
        $this->forwardRepository->assertEntityWasSavedThat($id, function (Forward $mailbox) use ($address): bool {
            self::assertSame($address, $mailbox->address);
            self::assertSame($this->domainNameRepository->get(DomainNameId::fromString(self::DOMAIN_1)), $mailbox->domainName);

            // Should not be changed
            self::assertSame('script:script.php', $mailbox->destination);

            return true;
        });
    }

    /** @test */
    public function it_rejects_when_address_already_exists_as_forward(): void
    {
        $this->expectExceptionObject(new AddressAlreadyExists('timith', new DomainNamePair('example1', 'com')));

        $this->handler->__invoke(
            new ChangeAddressOfForward(
                ForwardId::fromString(self::FORWARD_ID1),
                'timith',
                DomainNameId::fromString(self::DOMAIN_1),
            )
        );
    }
}
