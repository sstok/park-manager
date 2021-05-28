<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Messenger;

use ParkManager\Application\Command\DomainName\AddDomainName;
use ParkManager\Application\Command\DomainName\AssignDomainNameToOwner;
use ParkManager\Application\Command\DomainName\AssignDomainNameToSpace;
use ParkManager\Application\Command\DomainName\RemoveDomainName;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\CannotRemoveInUseDomainName;
use ParkManager\Domain\DomainName\Exception\CannotTransferInUseDomainName;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceAssignmentValidator;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;
use ParkManager\Tests\Domain\EntityHydrator;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\MockRepoResultSet;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

/**
 * @internal
 */
final class DomainNameSpaceAssignmentValidatorTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_ignores_other_unsupported_messages(): void
    {
        $spyingUsageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $spyingUsageValidatorProphecy->__invoke(Argument::any(), Argument::any())->shouldNotBeCalled();
        $spyingUsageValidator = $spyingUsageValidatorProphecy->reveal();

        $stack = new StackMiddleware();
        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock(), [$spyingUsageValidator]);

        $validator->handle(Envelope::wrap(AddDomainName::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', OrganizationId::ADMIN_ORG, 'example', 'com')), $stack);
    }

    /** @test */
    public function it_ignores_when_domain_name_has_no_space(): void
    {
        $id = DomainNameId::fromString('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494');

        $ownerRepository = new OwnerRepositoryMock();
        $usageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy->__invoke(Argument::any(), Argument::any())->shouldNotBeCalled();
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::register($id, new DomainNamePair('example', 'com'), $ownerRepository->getAdminOrganization())]), [$usageValidator]);
        $stack = new StackMiddleware();

        $validator->handle(Envelope::wrap(AssignDomainNameToSpace::with($id->toString(), '1438b200-242e-4688-917b-6fb8adf99947')), $stack);
    }

    /**
     * @test
     */
    public function it_it_passes_through_when_unused(): void
    {
        $messageObj = RemoveDomainName::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494');
        $id = $messageObj->id;

        $space = SpaceRepositoryMock::createSpace();
        $domainNamePair = new DomainNamePair('example', 'com');

        $usageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy->__invoke(Argument::which('id', $id), $space)->willReturn(
            [
                Mailbox::class => [],
            ]
        )->shouldBeCalled();
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::registerForSpace($id, $space, $domainNamePair)]), [$usageValidator]);
        $stack = new StackMiddleware();

        $validator->handle(Envelope::wrap($messageObj), $stack);
    }

    /**
     * @test
     * @dataProvider provideSupportedClasses
     */
    public function it_executes_validators(object $messageObj): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainNamePair = new DomainNamePair('example', 'com');
        $id = $messageObj->id;

        $usageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy->__invoke(Argument::which('id', $id), $space)->willReturn(
            [
                Mailbox::class => [],
                Forward::class => [
                    $entity1 = $this->createEntity(Forward::class, ForwardId::fromString('a55fdafc-0f3f-4869-acea-d5745afc4bd7')),
                    $entity2 = $this->createEntity(Forward::class, ForwardId::fromString('71f2f48e-feb4-4b70-bda7-51677919ce63')),
                ],
            ]
        );
        $usageValidator = $usageValidatorProphecy->reveal();

        $usageValidatorProphecy2 = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy2->__invoke(Argument::which('id', $id), $space)->willReturn(
            [
                SubDomain::class => [
                    $entity3 = $this->createEntity(SubDomain::class, SubDomainNameId::fromString('7e58def0-efec-4735-add8-f64bc512ed35')),
                    $entity4 = $this->createEntity(SubDomain::class, SubDomainNameId::fromString('dc6951bb-235b-4bf1-976a-50088c9d7a70')),
                ],
            ]
        );
        $usageValidator2 = $usageValidatorProphecy2->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::registerForSpace($id, $space, $domainNamePair)]), [$usageValidator, $usageValidator2]);
        $stack = new StackMiddleware();

        $this->expectExceptionObject(
            new CannotTransferInUseDomainName(
                $domainNamePair,
                $space->id,
                [
                    Forward::class => new MockRepoResultSet([
                        $entity1,
                        $entity2,
                    ]),
                    SubDomain::class => new MockRepoResultSet([
                        $entity3,
                        $entity4,
                    ]),
                ]
            )
        );

        $validator->handle(Envelope::wrap($messageObj), $stack);
    }

    public function provideSupportedClasses(): iterable
    {
        yield 'AssignDomainNameToSpace' => [AssignDomainNameToSpace::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', '1438b200-242e-4688-917b-6fb8adf99947')];

        yield 'AssignDomainNameToUser' => [AssignDomainNameToOwner::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', '1d2f8114-4b82-4962-b564-ba14c752c434')];

        yield 'AssignDomainNameToUser (admin)' => [AssignDomainNameToOwner::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', OrganizationId::ADMIN_ORG)];
    }

    /**
     * @test
     */
    public function it_executes_validators_for_remove_domain_name(): void
    {
        $messageObj = RemoveDomainName::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494');
        $id = $messageObj->id;

        $space = SpaceRepositoryMock::createSpace();
        $domainNamePair = new DomainNamePair('example', 'com');

        $usageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy->__invoke(Argument::which('id', $id), $space)->willReturn(
            [
                Mailbox::class => [],
                Forward::class => [
                    $entity1 = $this->createEntity(Forward::class, ForwardId::fromString('a55fdafc-0f3f-4869-acea-d5745afc4bd7')),
                    $entity2 = $this->createEntity(Forward::class, ForwardId::fromString('71f2f48e-feb4-4b70-bda7-51677919ce63')),
                ],
            ]
        );
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::registerForSpace($id, $space, $domainNamePair)]), [$usageValidator]);
        $stack = new StackMiddleware();

        $this->expectExceptionObject(
            new CannotRemoveInUseDomainName(
                $domainNamePair,
                $space->id,
                [
                    Forward::class => new MockRepoResultSet([
                        $entity1,
                        $entity2,
                    ]),
                ]
            )
        );

        $validator->handle(Envelope::wrap($messageObj), $stack);
    }

    private function createEntity(string $class, object $value): object
    {
        return EntityHydrator::hydrateEntity($class)->set('id', $value)->getEntity();
    }
}
