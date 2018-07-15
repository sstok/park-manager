<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\FormHandler;

use ParkManager\Component\ApplicationFoundation\Command\CommandBus;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Exception\BadMethodCallException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Throwable;

final class CommandBusFormHandler implements FormHandler
{
    private $form;
    private $commandBus;
    private $validator;

    private $handled = false;
    private $ready = false;

    /**
     * @var callable[]
     */
    private $exceptionFormatters = [];

    /**
     * @var callable|null
     */
    private $fallbackFormatter;

    public function __construct(FormInterface $form, CommandBus $commandBus, ?callable $validator = null)
    {
        $this->form = $form;
        $this->commandBus = $commandBus;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function mapException(string $exceptionClass, callable $formatter): void
    {
        if ($this->handled) {
            throw new AlreadySubmittedException('Cannot configure handler once Form is Handled.');
        }

        $this->exceptionFormatters[$exceptionClass] = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function setExceptionFallback(callable $formatter): void
    {
        if ($this->handled) {
            throw new AlreadySubmittedException('Cannot configure handler once Form is Handled.');
        }

        $this->fallbackFormatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest($request, bool $autoExecute = true)
    {
        if ($this->handled) {
            throw new AlreadySubmittedException('A form can only be handled once.');
        }

        $this->handled = true;
        $this->form->handleRequest($request);

        if (null !== $this->validator && !$this->form->isSubmitted()) {
            ($this->validator)($this->form->getData());
        }

        if ($this->form->isSubmitted() && $this->form->isValid()) {
            return $this->dispatch();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createView(): FormView
    {
        return $this->form->createView();
    }

    /**
     * {@inheritdoc}
     */
    private function dispatch()
    {
        try {
            $result = $this->commandBus->handle($this->form->getData());
            $this->ready = true;

            return $result;
        } catch (Throwable $e) {
            $exceptionName = \get_class($e);

            if (isset($this->exceptionFormatters[$exceptionName])) {
                $errors = $this->exceptionFormatters[$exceptionName]($e);
            } elseif (null !== $this->fallbackFormatter) {
                $errors = ($this->fallbackFormatter)($e);
            } else {
                throw $e;
            }

            $this->mapErrors($errors);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        if (!$this->handled) {
            throw new BadMethodCallException('handleRequest() must to be called before a Forms readiness can be checked.');
        }

        return $this->ready;
    }

    private function mapErrors($errors): void
    {
        if (!\is_array($errors)) {
            $errors = [null => [$errors]];
        }

        foreach ($errors as $formPath => $formErrors) {
            if (!\is_array($formErrors)) {
                $formErrors = [$formErrors];
            }

            $form = $this->form;

            if ('' !== (string) $formPath) {
                foreach (explode('.', $formPath) as $child) {
                    $form = $form->get($child);
                }
            }

            foreach ($formErrors as $error) {
                $form->addError($error);
            }
        }
    }
}
