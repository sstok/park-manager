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

namespace ParkManager\Component\FormHandler;

use ParkManager\Component\ApplicationFoundation\Command\CommandBus;
use ParkManager\Component\ApplicationFoundation\Query\QueryBus;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class ServiceBusFormFactory
{
    private $formFactory;
    private $queryBus;
    private $commandBus;
    private $commandValidator;

    public function __construct(FormFactoryInterface $formFactory, QueryBus $queryBus, CommandBus $commandBus, ?callable $commandValidator = null)
    {
        $this->formFactory = $formFactory;
        $this->queryBus = $queryBus;
        $this->commandBus = $commandBus;
        $this->commandValidator = $commandValidator;
    }

    /**
     * Creates a new FormHandler for handling a Command.
     *
     * The Command must be provided as the Form data.
     * Use {@link \Symfony\Component\Form\FormEvents::PRE_SET_DATA} to convert
     * the initial data to a correct Command object.
     *
     * @param string $formType
     * @param mixed  $data        The initial data (or a Command)
     * @param array  $formOptions
     *
     * @return FormHandler
     */
    public function createForCommand(string $formType, $data, array $formOptions = []): FormHandler
    {
        $form = $this->formFactory->create($formType, $data, $formOptions);
        $handler = new CommandBusFormHandler($form, $this->commandBus, $this->commandValidator);

        $this->configureMappingByForm($form, $handler);

        return $handler;
    }

    /**
     * Creates a new FormHandler for handling a Query, to allow modifying existing data.
     *
     * The Command must be provided as the Form data.
     * Use {@link \Symfony\Component\Form\FormEvents::PRE_SET_DATA} to convert
     * the initial data to a correct Command object.
     *
     * @param string $formType
     * @param object $query       The Query message object
     * @param array  $formOptions
     *
     * @return FormHandler
     */
    public function createForQuery(string $formType, object $query, array $formOptions = []): FormHandler
    {
        $form = $this->formFactory->create($formType, $this->queryBus->handle($query), $formOptions);
        $handler = new CommandBusFormHandler($form, $this->commandBus, $this->commandValidator);

        $this->configureMappingByForm($form, $handler);

        return $handler;
    }

    private function configureMappingByForm(FormInterface $form, FormHandler $handler): void
    {
        foreach ($form->getConfig()->getOption('exception_mapping', []) as $exceptionClass => $formatter) {
            if ('*' === $exceptionClass) {
                $handler->setExceptionFallback($formatter);
            } else {
                $handler->mapException($exceptionClass, $formatter);
            }
        }
    }
}
