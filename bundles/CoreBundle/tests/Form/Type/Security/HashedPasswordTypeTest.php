<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Form\Type\Security;

use ParkManager\Bundle\CoreBundle\Form\Type\Security\HashedPasswordType;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @internal
 */
final class HashedPasswordTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    /** @test */
    public function it_hashes_password(): void
    {
        $form = $this->factory->createBuilder()
            ->add('password', HashedPasswordType::class, [
                'algorithm' => static function (string $raw) {
                    return 'encoded(' . $raw . ')';
                },
            ])
            ->getForm()
        ;

        $form->submit([
            'password' => ['password' => 'Hello there'],
        ]);

        static::assertTrue($form->isValid());
        static::assertEquals(['password' => 'encoded(Hello there)'], $form->getData());
    }

    /** @test */
    public function it_asks_to_confirm_password(): void
    {
        $form = $this->factory->createBuilder()
            ->add('password', HashedPasswordType::class, [
                'algorithm' => static function (string $raw) {
                    return 'encoded(' . $raw . ')';
                },
                'password_confirm' => true,
            ])
            ->getForm()
        ;

        $form->submit([
            'password' => ['password' => ['first' => 'Hello there', 'second' => 'Hello there']],
        ]);

        static::assertTrue($form->isValid());
        static::assertEquals(['password' => 'encoded(Hello there)'], $form->getData());
    }
}
