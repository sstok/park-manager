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

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /** @test */
    public function it_works_with_empty_config(): void
    {
        $this->assertProcessedConfigurationEquals([[]], [
            'capabilities' => [
                'enabled' => true,
                'mapping' => [],
            ],
        ]);
    }

    /** @test */
    public function it_works_with_capabilities(): void
    {
        $this->assertProcessedConfigurationEquals(
            [
                [
                    'capabilities' => [
                        'enabled' => true,
                        'mapping' => [
                            'Foobar' => [
                                'capability' => 'MaximumAmountOfFoo',
                                'attributes' => ['baz' => 'bar'],
                            ],
                            'BlueBar' => ['capability' => 'QuotaAmountOfBlue'],
                            'CreateSomething' => 'MaximumAmountOfStuff',
                        ],
                    ],
                ],
            ],
            [
                'capabilities' => [
                    'enabled' => true,
                    'mapping' => [
                        'Foobar' => [
                            'capability' => 'MaximumAmountOfFoo',
                            'attributes' => ['baz' => 'bar'],
                        ],
                        'BlueBar' => [
                            'capability' => 'QuotaAmountOfBlue',
                            'attributes' => [],
                        ],
                        'CreateSomething' => [
                            'capability' => 'MaximumAmountOfStuff',
                            'attributes' => [],
                        ],
                    ],
                ],
            ],
            'capabilities'
        );
    }

    /** @test */
    public function it_requires_capabilities_mapping_attributes_are_scalar(): void
    {
        $this->assertPartialConfigurationIsInvalid(
            [
                [
                    'capabilities' => [
                        'enabled' => true,
                        'mapping' => [
                            'Foobar' => [
                                'capability' => 'MaximumAmountOfFoo',
                                'attributes' => ['baz' => false],
                            ],
                        ],
                    ],
                ],
            ],
            'capabilities',
            'Invalid configuration for path "park_manager_webhosting.capabilities.mapping.Foobar.attributes.baz": ' .
            'Attribute value expected to a property path as string.'
        );
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
