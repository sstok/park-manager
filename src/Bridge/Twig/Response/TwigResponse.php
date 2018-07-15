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

namespace ParkManager\Bridge\Twig\Response;

use ParkManager\Component\FormHandler\FormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

class TwigResponse extends Response
{
    private $template;
    private $variables;

    /**
     * @param string                 $template
     * @param array|Form|FormHandler $variables A Form or FormHandler object is passed as [form => createView()]
     * @param int                    $status
     * @param array                  $headers
     */
    public function __construct(string $template, $variables = [], int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        if (!is_array($variables)) {
            if ($variables instanceof Form || $variables instanceof FormHandler) {
                $variables = ['form' => $variables->createView()];
            } else {
                throw new \InvalidArgumentException(
                    sprintf('TwigResponse $variables expects an array, %s or %s object.', Form::class, FormHandler::class)
                );
            }
        }

        $this->template = $template;
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
