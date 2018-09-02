<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\DependencyInjection;

//use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
//use ParkManager\Bridge\PhpUnit\DefinitionArgumentEqualsServiceLocatorConstraint;
//use ParkManager\Bundle\ServiceBusPolicyGuardBundle\DependencyInjection\DependencyExtension;
//use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard\ExpressionPolicy;
//use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard\PolicyGuard;
//use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\ServiceA;
//use Symfony\Component\DependencyInjection\Definition;
//use Symfony\Component\DependencyInjection\Reference;
//use Symfony\Component\ExpressionLanguage\Expression;
//
///**
// * @internal
// */
//final class DependencyExtensionTest extends AbstractExtensionTestCase
//{
//    protected function getContainerExtensions(): array
//    {
//        return [new DependencyExtension];
//    }
//
//    /** @test */
//    public function it_works_for_empty_config()
//    {
//        $this->load();
//        $this->compile();
//        $this->container->get(PolicyGuard::class);
//
//        $this->assertContainerBuilderHasService(PolicyGuard::class);
//        $this->assertThat(
//            $this->container->findDefinition(PolicyGuard::class),
//            new DefinitionArgumentEqualsServiceLocatorConstraint($this->container, 1, [])
//        );
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PolicyGuard::class, 2, []);
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PolicyGuard::class, 3, []);
//    }
//
//    /** @test */
//    public function it_registers_policies()
//    {
//        $this->registerService('ServiceA', ServiceA::class);
//        $this->setParameter('foo', 'bar');
//
//        $this->load([
//            'namespaces' => [
//                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => [
//                    'permission' => 'my_service.getId() == message.id() and my_bar == "bar"',
//                    'variables' => [
//                        'my_service' => '@ServiceA',
//                        'my_bar' => '%foo%',
//                    ],
//                ],
//            ],
//            'classes' => [
//                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\Register' => [
//                    'permission' => 'my_service.getId() == message.id() and my_bar == "bar"',
//                    'variables' => [
//                        'my_service' => '@ServiceA',
//                        'my_bar' => '%foo%',
//                    ],
//                ],
//            ]
//        ]);
//
//        $this->compile();
//        $this->container->get(PolicyGuard::class);
//
//        $this->assertContainerBuilderHasService(PolicyGuard::class);
//        $this->assertThat(
//            $this->container->findDefinition(PolicyGuard::class),
//            new DefinitionArgumentEqualsServiceLocatorConstraint($this->container, 1, ['ServiceA' => new Reference('ServiceA')])
//        );
//
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PolicyGuard::class, 2, [
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => $this->createExpressionPolicyDef('my_service.getId() == message.id() and my_bar == "bar"', ['my_service' => 'ServiceA'], ['my_bar' => 'bar'])
//        ]);
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PolicyGuard::class, 3, [
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\Register' => $this->createExpressionPolicyDef('my_service.getId() == message.id() and my_bar == "bar"', ['my_service' => 'ServiceA'], ['my_bar' => 'bar'])
//        ]);
//    }
//
//    /** @test */
//    public function it_registers_policies_with_expends()
//    {
//        $this->registerService('ServiceA', ServiceA::class);
//        $this->setParameter('foo', 'bar');
//
//        $this->load([
//            'namespaces' => [
//                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\{Fixtures,Commands}' => [
//                    'permission' => 'my_service.getId() == message.id() and my_bar == "bar"',
//                    'variables' => [
//                        'my_service' => '@ServiceA',
//                        'my_bar' => '%foo%',
//                    ],
//                ],
//                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\{Query,Command}\Account' => null,
//            ],
//            'classes' => [
//                'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Command\Account' => true,
//            ]
//        ]);
//
//        $this->compile();
//        $this->container->get(PolicyGuard::class);
//
//        $this->assertContainerBuilderHasService(PolicyGuard::class);
//        $this->assertThat(
//            $this->container->findDefinition(PolicyGuard::class),
//            new DefinitionArgumentEqualsServiceLocatorConstraint($this->container, 1, ['ServiceA' => new Reference('ServiceA')])
//        );
//
//        $def = $this->createExpressionPolicyDef('my_service.getId() == message.id() and my_bar == "bar"', ['my_service' => 'ServiceA'], ['my_bar' => 'bar']);
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PolicyGuard::class, 2, [
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => $def,
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Commands' => $def,
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Query\Account' => null,
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Command\Account' => null,
//        ]);
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PolicyGuard::class, 3, [
//            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Command\Account' => true,
//        ]);
//    }
//
//    /**
//     * @test
//     * @dataProvider provideInvalidatePatterns
//     */
//    public function it_fails_for_invalid_policy_patterns(string $str)
//    {
//        $this->expectException(\InvalidArgumentException::class);
//        $this->expectExceptionMessage(sprintf('Policy "%s" contains invalid characters.', $str));
//
//        $this->load(['namespaces' => [$str => false]]);
//    }
//
//    public static function provideInvalidatePatterns(): array
//    {
//        return [
//            ['\\'],
//            ['Rollerworks?'],
//            ['Rollerworks\\Hello{}'],
//            ['Rollerworks\\Hello{Foo,foo,}'],
//            ['Rollerworks\\Hello{foo,foo}\\{nope}'],
//        ];
//    }
//
//    /**
//     * @test
//     * @dataProvider provideValidatePatterns
//     */
//    public function it_accepts_for_valid_policy_patterns(string $str)
//    {
//        $this->load(['namespaces' => [$str => false]]);
//
//        $this->compile();
//        $this->container->get(PolicyGuard::class);
//
//        $this->assertContainerBuilderHasService(PolicyGuard::class);
//    }
//
//    public static function provideValidatePatterns(): array
//    {
//        return [
//            ['\\stdClass'],
//            ['stdClass\\'],
//            ['Rollerworks\\Hello'],
//            ['Rollerworks\\Hello\\{Foo,foo,Bar}'],
//        ];
//    }
//
//    private function createExpressionPolicyDef(string $expression, array $serviceMap, array $variables): Definition
//    {
//        return new Definition(ExpressionPolicy::class, [
//            new Definition(Expression::class, [$expression]),
//            $serviceMap,
//            $variables,
//        ]);
//    }
//}
