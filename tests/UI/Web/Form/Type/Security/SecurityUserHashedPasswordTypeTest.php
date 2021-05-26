<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Security;

use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\UI\Web\Form\Type\Security\SecurityUserHashedPasswordType;
use RuntimeException;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @internal
 */
final class SecurityUserHashedPasswordTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    private object $hasherFactory;

    protected function setUp(): void
    {
        $passwordHasher = new class() implements PasswordHasherInterface {
            public function hash(string $plainPassword): string
            {
                return 'encoded(' . $plainPassword . ')';
            }

            public function verify(string $hashedPassword, string $plainPassword): bool
            {
                return false;
            }

            public function needsRehash(string $hashedPassword): bool
            {
                return false;
            }
        };

        $this->hasherFactory = new class($passwordHasher) implements PasswordHasherFactoryInterface {
            private $encoder;

            public function __construct($encoder)
            {
                $this->encoder = $encoder;
            }

            public function getPasswordHasher($user): PasswordHasherInterface
            {
                if ($user !== SecurityUser::class) {
                    throw new RuntimeException('Nope, that is not the right user.');
                }

                return $this->encoder;
            }
        };

        parent::setUp();
    }

    protected function getTypes(): array
    {
        return [
            new SecurityUserHashedPasswordType($this->hasherFactory),
        ];
    }

    /** @test */
    public function it_hashes_password(): void
    {
        $form = $this->factory->createBuilder()
            ->add('password', SecurityUserHashedPasswordType::class)
            ->getForm()
        ;

        $form->submit([
            'password' => ['password' => 'Hello there'],
        ]);

        self::assertTrue($form->isValid());
        self::assertSame(['password' => 'encoded(Hello there)'], $form->getData());
    }
}
