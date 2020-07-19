<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\EventListener;

use ParkManager\Domain\Exception\TranslatableException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

final class NotFoundExceptionListener implements EventSubscriberInterface
{
    private TranslatorInterface $translator;

    private TwigEnvironment $twig;

    public function __construct(TranslatorInterface $translator, TwigEnvironment $twig)
    {
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof TranslatableException) {
            $event->setResponse(new Response($this->twig->render('error.html.twig', ['message' => $this->translateMessage($exception)]), $exception->getCode()));
            $event->allowCustomResponseCode();
        }
    }

    private function translateMessage(TranslatableException $exception): string
    {
        $arguments = $exception->getTranslationArgs();

        foreach ($arguments as $key => $value) {
            if (\is_string($value) && \strncmp($key, '@', 1) === 0) {
                unset($arguments[$key]);
                $arguments[\mb_substr($key, 1)] = $this->translator->trans($value);
            }
        }

        return $this->translator->trans($exception->getTranslatorId(), $arguments);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 1],
        ];
    }
}
