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

namespace ParkManager\Bridge\Twig\Response;

use ParkManager\Component\FormHandler\FormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use function is_array;
use function sprintf;

class TwigResponse extends Response
{
    private $template;
    private $variables;

    /**
     * @param array|Form|FormHandler $variables A Form or FormHandler object is passed as [form => createView()]
     */
    public function __construct(string $template, $variables = [], int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        if (! is_array($variables)) {
            if (! ($variables instanceof Form) && ! ($variables instanceof FormHandler)) {
                throw new \InvalidArgumentException(
                    sprintf('TwigResponse $variables expects an array, %s or %s object.', Form::class, FormHandler::class)
                );
            }

            $variables = ['form' => $variables->createView()];
        }

        $this->template  = $template;
        $this->variables = $variables;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getTemplateVariables(): array
    {
        return $this->variables;
    }
}
