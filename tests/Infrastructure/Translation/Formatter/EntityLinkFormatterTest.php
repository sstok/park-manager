<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Translation\Formatter;

use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\User\User;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\Infrastructure\Translation\Formatter\EntityLinkFormatter;
use ParkManager\Infrastructure\Translation\Translator;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Translation\Translator as SfTranslator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
final class EntityLinkFormatterTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator(new SfTranslator('en'), new Container());
    }

    /** @test */
    public function it_formats_an_entity_object(): void
    {
        $user = UserRepositoryMock::createUser();

        $repositoryContainer = new Container();
        $repositoryContainer->set(User::class, new UserRepositoryMock([$user]));
        $repositoryLocator = new RepositoryLocator($repositoryContainer, []);

        $entityRenderer = $this->getEntityRenderer();

        $formatter = new EntityLinkFormatter($repositoryLocator, $entityRenderer);

        self::assertSame(
            '<a href="#dba1f6a0-3c5e-4cc2-9d10-2b8ddf3ce605">janE@example.com</a>',
            $formatter->format(new EntityLink($user), 'en', static fn (): string => '', $this->translator)
        );
    }

    /** @test */
    public function it_formats_an_entity_id_object(): void
    {
        $user = UserRepositoryMock::createUser();

        $repositoryContainer = new Container();
        $repositoryContainer->set(User::class, new UserRepositoryMock([$user]));
        $repositoryLocator = new RepositoryLocator($repositoryContainer, []);

        $entityRenderer = $this->getEntityRenderer();

        $formatter = new EntityLinkFormatter($repositoryLocator, $entityRenderer);

        self::assertSame(
            '<a href="#dba1f6a0-3c5e-4cc2-9d10-2b8ddf3ce605">janE@example.com</a>',
            $formatter->format(new EntityLink($user->id), 'en', static fn (): string => '', $this->translator)
        );
    }

    private function getEntityRenderer(): EntityRenderer
    {
        $loader = new ArrayLoader([
            'entity_rendering/user/user.html.twig' => <<<'TEMPLATE'
                {%- block link -%}
                    <a href="#{{ entity.id }}">{{ entity.email }}</a>
                {%- endblock -%}
                TEMPLATE,
        ]);

        return new EntityRenderer(
            new Environment($loader, ['optimizations' => 0]),
            $this->translator,
            []
        );
    }
}
