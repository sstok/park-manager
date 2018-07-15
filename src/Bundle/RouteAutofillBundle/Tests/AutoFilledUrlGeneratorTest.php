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

namespace ParkManager\Bundle\RouteAutofillBundle\Tests;

use ParkManager\Bundle\RouteAutofillBundle\AutoFilledUrlGenerator;
use ParkManager\Bundle\RouteAutofillBundle\MappingFileLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
final class AutoFilledUrlGeneratorTest extends TestCase
{
    /** @test */
    public const FOOBAR_RESULT = 'https://park-manager.com/webhosting';
    public const BLUE_BAR_RESULT = '//park-manager.com/';
    private const MOO_CAR_RESULT = '/webhosting/5/';

    private const FILLED_BAR_RESULT = 'https://park-manager.com/webhosting/4/2/1';

    /** @test */
    public function it_works_without_fill_mapping_set()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $generator = new AutoFilledUrlGenerator($this->createUrlGenerator(), $requestStack);

        self::assertEquals(self::FOOBAR_RESULT, $generator->generate('foobar', ['he' => 'bar']));
        self::assertEquals(self::BLUE_BAR_RESULT, $generator->generate('bluebar', [], UrlGeneratorInterface::RELATIVE_PATH));
        self::assertEquals(self::MOO_CAR_RESULT, $generator->generate('moocar', ['he' => 'bar', 'id' => 5]));
    }

    /** @test */
    public function it_fills_missing_route_parameters_using_current_request()
    {
        $request = new Request();
        $request->attributes->set('id', 50);
        $request->attributes->set('nope', 'yes');
        $request->attributes->set('name', 'you');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $generator = new AutoFilledUrlGenerator(
            $this->createUrlGenerator(),
            $requestStack,
            MappingFileLoader::fromArray(['filledd_bar' => ['id' => true, 'name' => 'foo', 'moo' => true]])
        );

        self::assertEquals(self::FOOBAR_RESULT, $generator->generate('foobar', ['he' => 'bar']));
        self::assertEquals(self::BLUE_BAR_RESULT, $generator->generate('bluebar', [], UrlGeneratorInterface::RELATIVE_PATH));
        self::assertEquals(self::MOO_CAR_RESULT, $generator->generate('moocar', ['he' => 'bar', 'id' => 5]));
        self::assertEquals(self::FILLED_BAR_RESULT, $generator->generate('filledd_bar', ['he' => 'foo', 'name' => 'bar']));
    }

    private function createEvent($result): GetResponseForControllerResultEvent
    {
        return new GetResponseForControllerResultEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $result
        );
    }

    private function assertResponseIsRedirect($response, string $expectedTargetUrl, int $expectedStatus = 302): void
    {
        /**
         * @var RedirectResponse $response
         */
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals($expectedTargetUrl, $response->getTargetUrl());
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }

    private function createUrlGenerator(): UrlGeneratorInterface
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('foobar', ['he' => 'bar'], UrlGeneratorInterface::ABSOLUTE_PATH)->willReturn(self::FOOBAR_RESULT);
        $urlGeneratorProphecy->generate('bluebar', [], UrlGeneratorInterface::RELATIVE_PATH)->willReturn(self::BLUE_BAR_RESULT);
        $urlGeneratorProphecy->generate('moocar', ['he' => 'bar', 'id' => 5], UrlGeneratorInterface::ABSOLUTE_PATH)->willReturn(self::MOO_CAR_RESULT);
        $urlGeneratorProphecy->generate('filledd_bar', ['id' => 50, 'he' => 'foo', 'name' => 'bar'], UrlGeneratorInterface::ABSOLUTE_PATH)->willReturn(self::FILLED_BAR_RESULT);

        return $urlGeneratorProphecy->reveal();
    }
}
