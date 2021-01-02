<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ViolationMapper
{
    private TranslatorInterface $translator;
    private FormRendererInterface $formRenderer;

    public function __construct(TranslatorInterface $translator, FormRendererInterface $formRenderer)
    {
        $this->translator = $translator;
        $this->formRenderer = $formRenderer;
    }

    public function mapViolation(ConstraintViolation $violation, FormInterface $form): void
    {
        $message = (string) $violation->getMessage();
        $messageTemplate = $violation->getMessageTemplate();
        $formPath = $violation->getPropertyPath();

        if ($formPath !== '') {
            $form = $this->resolveScope($formPath, $form);
        }

        [$message, $messageTemplate] = $this->resolveMessageLabel($message, $messageTemplate, $form);

        $form->addError(
            new FormError(
                $message,
                $messageTemplate,
                $violation->getParameters(),
                $violation->getPlural(),
                $violation
            )
        );
    }

    private function resolveScope(string $propertyPath, FormInterface $form): FormInterface
    {
        $violationPath = $form->getConfig()->getOption('violation_mapping')[$propertyPath] ?? null;
        $formPath = $violationPath ?? $propertyPath;

        // If the Form path is empty, the Violation is mapped to the root form
        if ($formPath === '') {
            return $form;
        }

        $scope = $form;

        foreach (\explode('.', $formPath) as $child) {
            if (! $scope->has($child)) {
                if ($violationPath === null) {
                    throw new InvalidConfigurationException(
                        \sprintf(
                            'Unable to resolve ViolationPath "%s" to a valid Form path, set the "violation_mapping" option with an explicit mapping.',
                            $formPath,
                        )
                    );
                }

                throw new InvalidConfigurationException(
                    \sprintf(
                        'Unable to resolve ViolationPath "%s" to a valid Form path, configured violation-path "%s" does not resolve a form.',
                        $formPath,
                        $violationPath,
                    )
                );
            }

            $scope = $scope->get($child);
        }

        return $scope;
    }

    private function resolveMessageLabel(string $message, string $messageTemplate, FormInterface $form): array
    {
        if (\mb_strpos($message, '{{ label }}') === false && \mb_strpos($messageTemplate, '{{ label }}') === false) {
            return [$message, $messageTemplate];
        }

        $labelFormat = $form->getConfig()->getOption('label_format');

        if ($labelFormat !== null) {
            $label = \str_replace(
                [
                    '%name%',
                    '%id%',
                ],
                [
                    $form->getName(),
                    (string) $form->getPropertyPath(),
                ],
                $labelFormat
            );
        } else {
            $label = $form->getConfig()->getOption('label');
        }

        if ($label !== false) {
            if ($label === null) {
                $label = $this->formRenderer->humanize($form->getName());
            }

            $label = $this->translator->trans(
                $label,
                $form->getConfig()->getOption('label_translation_parameters', []),
                $form->getConfig()->getOption('translation_domain')
            );

            $message = \str_replace('{{ label }}', $label, $message);
            $messageTemplate = \str_replace('{{ label }}', $label, $messageTemplate);
        }

        return [$message, $messageTemplate];
    }
}
