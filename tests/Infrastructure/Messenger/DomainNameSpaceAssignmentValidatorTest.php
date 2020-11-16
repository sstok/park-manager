<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Messenger;

use ParkManager\Application\Command\DomainName\AddDomainName;
use ParkManager\Application\Command\DomainName\AssignDomainNameToSpace;
use ParkManager\Application\Command\DomainName\AssignDomainNameToUser;
use ParkManager\Application\Command\DomainName\RemoveDomainName;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\CannotRemoveInUseDomainName;
use ParkManager\Domain\DomainName\Exception\CannotTransferInUseDomainName;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceAssignmentValidator;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
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

        $validator->handle(Envelope::wrap(AddDomainName::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', null, 'example', 'com')), $stack);
    }

    /** @test */
    public function it_ignores_when_domain_name_has_no_space(): void
    {
        $id = DomainNameId::fromString('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494');

        $usageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy->__invoke(Argument::any(), Argument::any())->shouldNotBeCalled();
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::register($id, new DomainNamePair('example', 'com'), null)]), [$usageValidator]);
        $stack = new StackMiddleware();

        $validator->handle(Envelope::wrap(AssignDomainNameToSpace::with($id->toString(), '1438b200-242e-4688-917b-6fb8adf99947')), $stack);
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
        $usageValidatorProphecy->__invoke(Argument::which('id', $id), $space)->willThrow(new CannotTransferInUseDomainName($domainNamePair, $space->id, 'email', '133984892'));
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::registerForSpace($id, $space, $domainNamePair)]), [$usageValidator]);
        $stack = new StackMiddleware();

        $this->expectExceptionObject(new CannotTransferInUseDomainName($domainNamePair, $space->id, 'email', '133984892'));

        $validator->handle(Envelope::wrap($messageObj), $stack);
    }

    public function provideSupportedClasses(): iterable
    {
        yield 'AssignDomainNameToSpace' => [AssignDomainNameToSpace::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', '1438b200-242e-4688-917b-6fb8adf99947')];

        yield 'AssignDomainNameToUser' => [AssignDomainNameToUser::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', '1d2f8114-4b82-4962-b564-ba14c752c434')];

        yield 'AssignDomainNameToUser (admin)' => [AssignDomainNameToUser::with('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494', null)];
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
        $usageValidatorProphecy->__invoke(Argument::which('id', $id), $space)->willThrow(new CannotTransferInUseDomainName($domainNamePair, $space->id, 'email', '133984892'));
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::registerForSpace($id, $space, $domainNamePair)]), [$usageValidator]);
        $stack = new StackMiddleware();

        $this->expectExceptionObject(new CannotRemoveInUseDomainName($domainNamePair, $space->id, 'email', '133984892'));

        $validator->handle(Envelope::wrap($messageObj), $stack);
    }
}
