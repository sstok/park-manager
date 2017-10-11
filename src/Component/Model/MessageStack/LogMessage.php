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

namespace ParkManager\Component\Model\MessageStack;

/**
 * A LogMessage contains informational information for upper layers.
 *
 * This information is intended to be shared with the UI layer and
 * MUST NOT contain any sensitive information about the inner system.
 * LogMessages are not system exceptions, they are informational messages.
 *
 * Eg. A LogMessage can be used to inform a sub-operation was skipped and why.
 *
 * Tip: The messageTemplate is rendered by the UI layer.
 * But the message property can contain
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class LogMessage
{
    /**
     * @var string
     */
    public $type;

    /**
     * The template for the error message.
     *
     * @var string|null
     */
    public $messageTemplate;

    /**
     * The parameters that should be substituted in the message template.
     *
     * @var array
     */
    public $messageParameters;

    /**
     * The value for error message pluralization.
     *
     * @var int|null
     */
    public $messagePluralization;

    /**
     * @var string[]
     */
    public $translatedParameters;

    /**
     * @var string
     */
    public $systemMessage;

    /**
     * @param string      $type              Type of the message (error, warning, notice)
     * @param string|null $messageTemplate   The template for the message
     * @param array       $messageParameters The parameters that should be
     *                                       substituted in the message template
     * @param string      $systemMessage     The (translated) message
     */
    public function __construct(string $type, string $messageTemplate, array $messageParameters = [], ?string $systemMessage = null)
    {
        $this->type = $type;
        $this->messageTemplate = $messageTemplate;
        $this->messageParameters = $messageParameters;
        $this->systemMessage = $systemMessage;
    }

    /**
     * @param int|null $messagePluralization
     *
     * @return $this
     */
    public function withPlural(?int $messagePluralization)
    {
        $this->messagePluralization = $messagePluralization;

        return $this;
    }

    /**
     * @param array $translatedParameters An array of parameter names that need
     *                                    to be translated prior to there usage
     *
     * @return $this
     */
    public function translateParameters(array $translatedParameters)
    {
        $this->translatedParameters = $translatedParameters;

        return $this;
    }

    public function __toString(): string
    {
        return $this->systemMessage;
    }

    public static function notice(string $messageTemplate, array $messageParameters = [], ?string $systemMessage = null)
    {
        return new static('notice', $messageTemplate, $messageParameters, $systemMessage);
    }

    public static function warning(string $messageTemplate, array $messageParameters = [], ?string $systemMessage = null)
    {
        return new static('warning', $messageTemplate, $messageParameters, $systemMessage);
    }

    public static function error(string $messageTemplate, array $messageParameters = [], ?string $systemMessage = null)
    {
        return new static('error', $messageTemplate, $messageParameters, $systemMessage);
    }
}
