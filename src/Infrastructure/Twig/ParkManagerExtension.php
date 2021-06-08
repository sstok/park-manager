<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use ParkManager\Domain\ByteSize;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use Stringable;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use TypeError;

final class ParkManagerExtension extends AbstractExtension
{
    private object $argumentsTranslator;

    public function __construct(
        private TranslatorInterface $translator,
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository
    ) {
        $this->argumentsTranslator = new class($translator) implements TranslatorInterface {
            private Environment $env;

            public function __construct(private TranslatorInterface $wrappedTranslator)
            {
            }

            public function setEnv(Environment $env): void
            {
                $this->env = $env;
            }

            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                foreach ($parameters as $name => $value) {
                    $parameters[$name] = twig_escape_filter($this->env, $value);
                }

                return $this->wrappedTranslator->trans($id, $parameters, $domain, $locale);
            }

            public function getLocale(): string
            {
                return $this->wrappedTranslator->getLocale();
            }
        };
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_safe', [$this, 'trans'], ['needs_environment' => true, 'is_safe' => ['all']]),
            new TwigFilter('wordwrap', [$this, 'wordwrap'], ['needs_environment' => true, 'is_safe' => ['all']]),
            new TwigFilter('render_byte_size', [$this, 'renderByteSize'], ['is_safe' => ['all']]),
            new TwigFilter('merge_attr_class', [$this, 'mergeAttrClass']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_current_user', [$this, 'getCurrentUser']),
            new TwigFunction('create_byte_size', [ByteSize::class, 'fromString']),
        ];
    }

    /**
     * @param array<string, mixed>|string $arguments Can be the locale as a string when $message is a TranslatableInterface
     */
    public function trans(Environment $env, TranslatableInterface | Stringable | string | null $message, array | string $arguments = [], ?string $domain = null, ?string $locale = null, ?int $count = null): string
    {
        if ($message instanceof TranslatableInterface) {
            if ($arguments !== [] && ! \is_string($arguments)) {
                throw new TypeError(sprintf('Argument 2 passed to "%s()" must be a locale passed as a string when the message is a "%s", "%s" given.', __METHOD__, TranslatableInterface::class, get_debug_type($arguments)));
            }

            $this->argumentsTranslator->setEnv($env);

            return $message->trans($this->argumentsTranslator, $locale ?? (\is_string($arguments) ? $arguments : null));
        }

        if (! \is_array($arguments)) {
            throw new TypeError(sprintf('Unless the message is a "%s", argument 2 passed to "%s()" must be an array of parameters, "%s" given.', TranslatableInterface::class, __METHOD__, get_debug_type($arguments)));
        }

        $message = (string) $message;

        if ($message === '') {
            return '';
        }

        foreach ($arguments as $name => $value) {
            $arguments[$name] = twig_escape_filter($env, $value);
        }

        if ($count !== null) {
            $arguments['%count%'] = $count;
        }

        return $this->translator->trans($message, $arguments, $domain, $locale);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public function mergeAttrClass(array $attributes, string $class, bool $append = false): array
    {
        if (! isset($attributes['class'])) {
            $attributes['class'] = '';
        }

        if ($append) {
            $attributes['class'] .= ' ' . $class;
        } else {
            $attributes['class'] = $class . ' ' . $attributes['class'];
        }

        $attributes['class'] = trim($attributes['class']);

        return $attributes;
    }

    public function renderByteSize(ByteSize $value): string
    {
        if ($value->isInf()) {
            return $this->translator->trans('byte_size.inf');
        }

        $unit = mb_strtolower($value->getUnit());

        if ($unit === 'b') {
            $unit = 'byte';
        }

        return $this->translator->trans(
            'byte_size.format',
            [
                'value' => $value->getNormSize(),
                'unit' => $this->translator->trans('byte_size.' . $unit),
            ]
        );
    }

    public function getCurrentUser(): User
    {
        static $currentToken, $currentUser;

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            throw new AccessDeniedException();
        }

        if ($currentToken !== $token) {
            $currentToken = $token;
            $currentUser = $this->userRepository->get(UserId::fromString($token->getUserIdentifier()));
        }

        return $currentUser;
    }

    public function wordwrap(Environment $env, string | Stringable | UnicodeString $text, int $width = 75, string $break = "\n", bool $cut = false, bool $escape = true): string
    {
        if ($escape) {
            $text = twig_escape_filter($env, (string) $text);
        }

        if (! $text instanceof UnicodeString) {
            $text = new UnicodeString((string) $text);
        }

        return $text->wordwrap($width, $break, $cut)->toString();
    }
}

// Force autoloading of the EscaperExtension as we need the twig_escape_filter() function
class_exists(EscaperExtension::class);
