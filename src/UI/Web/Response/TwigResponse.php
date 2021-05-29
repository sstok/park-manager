<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Response;

use InvalidArgumentException;
use Symfony\Component\Form\FormInterface as Form;
use Symfony\Component\HttpFoundation\Response;

class TwigResponse extends Response
{
    private string $template;
    /** @var array<string, mixed> */
    private array $variables;

    /**
     * @param array<string, mixed>|Form                $variables A Form object is passed as [form => createView()]
     * @param array<string, string|array<int, string>> $headers
     */
    public function __construct(string $template, array | Form $variables = [], int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        $this->setTemplateVariables($variables);

        $this->template = $template;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param array<string, mixed>|Form $variables A Form object is passed as [form => createView()]
     */
    public function setTemplateVariables(array | Form $variables): void
    {
        if (! \is_array($variables)) {
            if (! $variables instanceof Form) {
                throw new InvalidArgumentException(sprintf('TwigResponse $variables expects an array or %s object.', Form::class));
            }

            $variables = ['form' => $variables->createView()];
        }

        $this->variables = $variables;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateVariables(): array
    {
        return $this->variables;
    }
}
