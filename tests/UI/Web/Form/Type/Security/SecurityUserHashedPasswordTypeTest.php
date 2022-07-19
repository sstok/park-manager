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
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @internal
 */
final class SecurityUserHashedPasswordTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    private PasswordHasherFactoryInterface $hasherFactory;

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
            public function __construct(private PasswordHasherInterface $encoder)
            {
            }

            public function getPasswordHasher(string | PasswordAuthenticatedUserInterface | PasswordHasherAwareInterface $user): PasswordHasherInterface
            {
                if ($user !== SecurityUser::class) {
                    throw new RuntimeException('Nope, that is not the right user.');
                }

                return $this->encoder;
            }
        };

        parent::setUp();
    }

    /**
     * @return FormTypeInterface[]
     */
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
