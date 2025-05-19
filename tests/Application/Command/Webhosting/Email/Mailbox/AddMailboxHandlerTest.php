<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Email\Mailbox;

use Lifthill\Component\Common\Application\PasswordHasher;
use Lifthill\Component\Common\Domain\Model\ByteSize;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Command\Webhosting\Email\Mailbox\AddMailbox;
use ParkManager\Application\Command\Webhosting\Email\Mailbox\AddMailboxHandler;
use ParkManager\Application\Service\SpaceConstraint\ConstraintsChecker;
use ParkManager\Application\Service\SystemGateway;
use ParkManager\Application\Service\SystemGateway\Webhosting\CreateMailbox;
use ParkManager\Application\Service\SystemGateway\Webhosting\CreateMailboxResult;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\ForwardRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\MailboxRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class AddMailboxHandlerTest extends TestCase
{
    use ProphecyTrait;

    private const DOMAIN_1 = '017d0b56-2d66-c966-b1ec-fc190f8a5c5f';

    private SpaceRepositoryMock $spaceRepository;
    private DomainNameRepositoryMock $domainNameRepository;
    private MailboxRepository $mailboxRepository;
    private ForwardRepositoryMock $forwardRepository;
    private AddMailboxHandler $handler;

    protected function setUp(): void
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(),
        ]);

        $this->domainNameRepository = new DomainNameRepositoryMock([
            $domainName = DomainName::registerForSpace(
                DomainNameId::fromString(self::DOMAIN_1),
                $space1,
                new DomainNamePair('example1', 'com')
            ),
        ]);
        $this->spaceRepository->save($space1);
        $this->spaceRepository->resetRecordingState();

        $this->mailboxRepository = new MailboxRepositoryMock([
            new Mailbox(MailboxId::fromString('017d157a-4ca8-4275-01af-1e399720504a'), $space1, 'timith', $domainName, ByteSize::inf(), 'nope'),
        ]);
        $this->forwardRepository = new ForwardRepositoryMock([
            Forward::toScript(ForwardId::fromString('017d156d-8241-da3f-4328-158eaa59ec15'), $space1, 'administration', $domainName, 'script.php'),
        ]);

        $constraintCheckerProphecy = $this->prophesize(ConstraintsChecker::class);
        $constraintCheckerProphecy->allowNewMailboxes(SpaceId::fromString(SpaceRepositoryMock::ID1), Argument::any())->shouldBeCalled();

        $systemGatewayProphecy = $this->prophesize(SystemGateway::class);
        $systemGatewayProphecy->execute(Argument::type(CreateMailbox::class))->willReturn(new CreateMailboxResult([]));

        $passwordHasher = new class implements PasswordHasher {
            public function hash(HiddenString $password): string
            {
                return \sprintf('hashed(%s)', $password->getString());
            }
        };

        $this->handler = new AddMailboxHandler(
            $this->mailboxRepository,
            $this->forwardRepository,
            $this->spaceRepository,
            $this->domainNameRepository,
            $constraintCheckerProphecy->reveal(),
            $passwordHasher,
            $systemGatewayProphecy->reveal(),
        );
    }

    /** @test */
    public function it_adds_mailbox(): void
    {
        $this->handler->__invoke(
            new AddMailbox(
                $space = SpaceId::fromString(SpaceRepositoryMock::ID1),
                $id = MailboxId::create(),
                $address = 'allison.hanson',
                $domainName = DomainNameId::fromString(self::DOMAIN_1),
                new HiddenString('NullNullNullNullNull'),
                $size = new ByteSize(10, 'Gib')
            )
        );

        $this->mailboxRepository->assertEntitiesCountWasSaved(1);
        $this->mailboxRepository->assertEntitiesWereSaved([
            new Mailbox(
                $id,
                $this->spaceRepository->get($space),
                $address,
                $this->domainNameRepository->get($domainName),
                $size,
                'hashed(NullNullNullNullNull)'
            ),
        ]);
    }

    /** @test */
    public function it_rejects_when_address_already_exists_as_forward(): void
    {
        $this->expectExceptionObject(new AddressAlreadyExists('administration', new DomainNamePair('example1', 'com')));

        $this->handler->__invoke(
            new AddMailbox(
                SpaceId::fromString(SpaceRepositoryMock::ID1),
                MailboxId::create(),
                'administration',
                DomainNameId::fromString(self::DOMAIN_1),
                new HiddenString('NullNullNullNullNull'),
            )
        );
    }
}
