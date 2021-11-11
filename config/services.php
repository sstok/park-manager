<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Persistence\ObjectManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use ParkManager\Application\Service\OwnershipUsageList;
use ParkManager\Application\Service\PdpManager;
use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Application\Service\TLS\CertificateFactoryImpl;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\OwnerControlledRepository;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceAssignmentValidator;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;
use ParkManager\Infrastructure\Pdp\PsrStorageFactory;
use ParkManager\Infrastructure\Security\Guard\FormAuthenticator;
use ParkManager\Infrastructure\Security\PermissionExpressionProvider;
use ParkManager\Infrastructure\Security\UserProvider;
use ParkManager\Infrastructure\Security\Voter\SuperAdminVoter;
use ParkManager\Infrastructure\Security\Voter\SwitchUserVoter;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\Infrastructure\Service\Webhosting\FtpPasswordHasher;
use ParkManager\Infrastructure\Translation\Formatter\EntityLinkFormatter;
use ParkManager\Infrastructure\Translation\Translator;
use ParkManager\UI\Web\ArgumentResolver\ModelResolver;
use ParkManager\UI\Web\ArgumentResolver\SplitTokenResolver;
use Psr\Container\ContainerInterface;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitTokenFactory;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

return static function (ContainerConfigurator $c): void {
    $di = $c->services()->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$commandBus', service('park_manager.command_bus'))
        ->bind('$avatarStorage', service('park_manager.avatar_storage'))
        ->bind('$acceptedLocales', '%accepted_locales%')
        ->bind(Session::class, service('session'))
        ->bind(FormRendererInterface::class, service('twig.form.renderer'))
        ->bind(ObjectManager::class, service('doctrine.orm.default_entity_manager'))
        ->bind(ContainerInterface::class, service('service_container'));

    $di->instanceof(DomainNameSpaceUsageValidator::class)
        ->tag('park_manager.command_bus.domain_name_space_usage_validator');

    $di->instanceof(OwnerControlledRepository::class)
        ->tag('park_manager.owner_controlled_repository');

    $di->set(PsrStorageFactory::class)->args([
        inline_service(Psr16Cache::class)->arg(0, service('cache.public_prefix_db')),
        service('http_client'),
    ]);

    $di->set(PdpManager::class)->args([
        expr(sprintf("service('%s').createPublicSuffixListStorage()", addslashes(PsrStorageFactory::class))),
        expr(sprintf("service('%s').createTopLevelDomainListStorage()", addslashes(PsrStorageFactory::class))),
    ]);

    $di->set('park_manager.avatar_storage', Filesystem::class)
        ->args([
            inline_service(LocalFilesystemAdapter::class)->args(['%kernel.project_dir%/var/avatars'])
        ])
    ;

    // Note: Repositories are loaded separate as autowiring the entire Domain is not
    // possible. Entities and other models must not be registered as services.
    //
    // But registering the repositories separate breaks their singlyImplemented
    // interface aliasing.
    $di->load('ParkManager\\', __DIR__ . '/../src/**/*Repository.php');

    // Register the handler, not the actual commands.
    $di->load('ParkManager\\Application\\Command\\', __DIR__ . '/../src/Application/Command/**/*Handler.php')
        ->tag('messenger.message_handler', ['bus' => 'park_manager.command_bus']);

    $di->load('ParkManager\\', __DIR__ . '/../src/*')
        ->exclude([
            __DIR__ . '/../src/Kernel.php',
            __DIR__ . '/../src/**/*Event.php',
            __DIR__ . '/../src/{Domain,DataFixtures}',
            __DIR__ . '/../src/Application/{Command,Event}',
            __DIR__ . '/../src/Application/Service/TLS/Violation',
            __DIR__ . '/../src/Application/Service/PdpManager.php',
            __DIR__ . '/../src/Application/Service/SystemGateway/**',
            __DIR__ . '/../src/Infrastructure/{Doctrine,Pdp}',
            __DIR__ . '/../src/Infrastructure/Security/*User.php',
            __DIR__ . '/../src/Infrastructure/Security/{Permission, Voter}',
            __DIR__ . '/../src/UI/Web/Form/{ConfirmationHandler,DataTransformer,DataMapper}',
        ]);

    $di->set(Translator::class)
        ->autowire(false)
        ->decorate('translator')
        ->args([
            service('.inner'),
            service_locator([
                EntityLink::class => service(EntityLinkFormatter::class),
            ])
        ])
    ;

    $di->get(EntityRenderer::class)->args([
        service('twig'),
        service('translator'),
        abstract_arg('Entity full name to label mapping')
    ]);

    $di->get(CertificateFactoryImpl::class)
        ->args(['%env(base64:TLS_STORAGE_PUBKEY)%']);

    $di->load('ParkManager\\Infrastructure\\Security\\Permission\\', __DIR__ . '/../src/Infrastructure/Security/Permission/**/*Decider.php')
        ->tag('park_manager.security.permission_decider');

    $di->set(RepositoryLocator::class)->args([
        abstract_arg('Entity repository service-locator'),
        abstract_arg('Entity short-aliases, either "user" or "webhosting.space"')
    ]);

    $di->set(FtpPasswordHasher::class)->args([inline_service(NativePasswordHasher::class)]);


    $di->get(SplitTokenResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 255])
        ->autoconfigure(false);

    $di->get(ModelResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 255])
        ->autoconfigure(false)
        ->args([
            abstract_arg('Entity repository service-locator'),
            abstract_arg('Model VO reconstruction methods'),
        ]);

    $di->get(DomainNameSpaceAssignmentValidator::class)
        ->arg(1, tagged_iterator('park_manager.command_bus.domain_name_space_usage_validator'));

    $di->get(OwnershipUsageList::class)
        ->arg(0, iterator([
            Space::class => service(SpaceRepository::class),
            DomainName::class => service(DomainNameRepository::class),
        ]));

    $di->set(Argon2SplitTokenFactory::class);
    $di->alias(SplitTokenFactory::class, Argon2SplitTokenFactory::class);

    $di->load('ParkManager\\UI\\Console\\', __DIR__ . '/../src/UI/Console/**/*Command.php')
        ->tag('console.command');

    // -- Security
    $di->set('park_manager.security.user_provider', UserProvider::class);
    $di->set('park_manager.security.guard.form', FormAuthenticator::class);

    // After AuthenticatedVoter
    $di->set(SwitchUserVoter::class)
        ->tag('security.voter', ['priority' => 252]);

    // After AuthenticatedVoter and SwitchUserVoter. But before Role voters.
    $di->set(SuperAdminVoter::class)
        ->tag('security.voter', ['priority' => 248]);

    $di->set(PermissionExpressionProvider::class)
        ->tag('security.expression_language_provider')
        ->autoconfigure(false);
};
