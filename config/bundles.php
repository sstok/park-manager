<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\WebServerBundle\WebServerBundle::class => ['dev' => true],
    Rollerworks\Bundle\RouteAutowiringBundle\RollerworksRouteAutowiringBundle::class => ['all' => true],
    ParkManager\Bundle\ServiceBusBundle\ParkManagerServiceBusBundle::class => ['all' => true],
    ParkManager\Bundle\ServiceBusPolicyGuardBundle\ParkManagerServiceBusPolicyGuardBundle::class => ['all' => true],
    \ParkManager\Core\ParkManagerCore::class => ['all' => true],
    ParkManager\Bundle\UserBundle\ParkManagerUserBundle::class => ['all' => true],
    ParkManager\Bundle\TestBundle\ParkManagerTestBundle::class => ['test' => true],
    ParkManager\Module\Webhosting\ParkManagerWebhostingBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class => ['all' => true],
    Sylius\Bundle\MailerBundle\SyliusMailerBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    Hostnet\Bundle\FormHandlerBundle\HostnetFormHandlerBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
];
