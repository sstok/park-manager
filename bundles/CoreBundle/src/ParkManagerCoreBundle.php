<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use ParkManager\Bundle\CoreBundle\DependencyInjection\DependencyExtension;
use ParkManager\Bundle\CoreBundle\DependencyInjection\EnvVariableResource;
use ParkManager\Bundle\CoreBundle\Http\CookiesRequestMatcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParkManagerCoreBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new DependencyExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        $path = $this->getPath() . '/src/Model/';
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver([$this->getPath() . '/src/Doctrine/SecurityMapping' => 'Rollerworks\\Component\\SplitToken']));
        $container->addCompilerPass(DoctrineOrmMappingsPass::createAnnotationMappingDriver([$path => $this->getNamespace() . '\\Model'], [$path]));
    }

    public static function setAppConfiguration(ContainerBuilder $container): void
    {
        $container->addResource(new EnvVariableResource('PRIMARY_HOST'));
        $container->addResource(new EnvVariableResource('ENABLE_HTTPS'));

        $isSecure = (($_ENV['ENABLE_HTTPS'] ?? 'false') === 'true');
        $primaryHost = $_ENV['PRIMARY_HOST'] ?? null;

        $container->setParameter('park_manager.config.primary_host', $_ENV['PRIMARY_HOST'] ?? '');
        $container->setParameter('park_manager.config.requires_channel', $isSecure ? 'https' : null);
        $container->setParameter('park_manager.config.is_secure', $isSecure);

        $container->register('park_manager.section.admin.request_matcher', RequestMatcher::class)->setArguments(['^/admin/']);
        $container->register('park_manager.section.client.request_matcher', RequestMatcher::class)->setArguments(['^/(?!(api|admin)/)']);
        $container->register('park_manager.section.api.request_matcher', RequestMatcher::class)->setArguments(['/api']);

        $container->register('park_manager.section.private.request_matcher', CookiesRequestMatcher::class)
            ->setArguments(['^/(?!(api|admin)/)'])
            ->addMethodCall('matchCookies', [['_private_section' => '^true$']])
        ;

        if ($primaryHost === null) {
            return;
        }

        $container->getDefinition('park_manager.section.admin.request_matcher')->setArgument(1, $primaryHost);
        $container->getDefinition('park_manager.section.private.request_matcher')->setArgument(1, $primaryHost);
        $container->getDefinition('park_manager.section.api.request_matcher')->setArguments(['/', '^api\.']);
    }
}
