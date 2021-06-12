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
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

final class TranslatableExceptionListener implements EventSubscriberInterface
{
    public function __construct(private TranslatorInterface $translator, private TwigEnvironment $twig)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof TranslatableException) {
            $event->setResponse(new Response($this->twig->render('error.html.twig', ['message' => $this->translateMessage($exception)]), ((int) $exception->getCode()) ?: Response::HTTP_BAD_REQUEST));
            $event->allowCustomResponseCode();
        }
    }

    private function translateMessage(TranslatableException $exception): string
    {
        $translatorId = $exception->getTranslatorId();

        if ($translatorId instanceof TranslatableInterface) {
            return $translatorId->trans($this->translator);
        }

        return $this->translator->trans($translatorId, [], 'validators');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 1],
        ];
    }
}
