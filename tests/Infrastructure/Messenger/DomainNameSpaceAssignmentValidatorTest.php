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
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceAssignmentValidator;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

/**
 * @internal
 */
final class DomainNameSpaceAssignmentValidatorTest extends TestCase
{
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

    /** @test */
    public function it_executes_validators(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $id = DomainNameId::fromString('ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494');

        $usageValidatorProphecy = $this->prophesize(DomainNameSpaceUsageValidator::class);
        $usageValidatorProphecy->__invoke(Argument::which('id', $id), $space)->willThrow(new \InvalidArgumentException('I refuse to let this one go!'));
        $usageValidator = $usageValidatorProphecy->reveal();

        $validator = new DomainNameSpaceAssignmentValidator(new DomainNameRepositoryMock([DomainName::registerForSpace($id, $space, new DomainNamePair('example', 'com'))]), [$usageValidator]);
        $stack = new StackMiddleware();

        $this->expectExceptionObject(new \InvalidArgumentException('I refuse to let this one go!'));

        $validator->handle(Envelope::wrap(AssignDomainNameToSpace::with($id->toString(), '1438b200-242e-4688-917b-6fb8adf99947')), $stack);
    }
}
