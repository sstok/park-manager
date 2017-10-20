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

namespace ParkManager\Module\Webhosting\Tests\Infrastructure\Symfony\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Module\Webhosting\Infrastructure\Symfony\DependencyInjection\Compiler\CommandToCapabilitiesGuardPass;
use ParkManager\Module\Webhosting\Service\Package\CommandToCapabilitiesGuard;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\MailboxCountCount;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\StorageSpaceQuota;
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Mailbox\CreateMailbox;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class CommandToCapabilitiesGuardPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_keeps_original_configuration_when_no_services_are_found()
    {
        $this->registerService(CommandToCapabilitiesGuard::class, CommandToCapabilitiesGuard::class)
            ->setArguments([null, $expected = [CreateMailbox::class => [MailboxCountCount::class]]]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(CommandToCapabilitiesGuard::class, 1, $expected);
    }

    /** @test */
    public function it_finds_subscribing_capabilities()
    {
        $this->registerService(MailboxCountCount::class, MailboxCountCount::class)
            ->addTag('park_manager.webhosting_capability');
        $this->registerService(StorageSpaceQuota::class, StorageSpaceQuota::class)
            ->addTag('park_manager.webhosting_capability');

        $this->registerService(CommandToCapabilitiesGuard::class, CommandToCapabilitiesGuard::class)
            ->setArguments([null, []]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            CommandToCapabilitiesGuard::class,
            1,
            [CreateMailbox::class => [MailboxCountCount::class]]
        );
    }

    /** @test */
    public function it_ensures_unique_registrations()
    {
        $this->registerService(MailboxCountCount::class, MailboxCountCount::class)
            ->addTag('park_manager.webhosting_capability');

        $this->registerService(CommandToCapabilitiesGuard::class, CommandToCapabilitiesGuard::class)
            ->setArguments([null, [CreateMailbox::class => [MailboxCountCount::class]]]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            CommandToCapabilitiesGuard::class,
            1,
            [CreateMailbox::class => [MailboxCountCount::class]]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CommandToCapabilitiesGuardPass());
    }
}
