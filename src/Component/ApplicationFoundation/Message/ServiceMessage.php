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

namespace ParkManager\Component\ApplicationFoundation\Message;

/**
 * A ServiceMessage contains an informational message for the UI
 * and Infrastructure layers.
 *
 * This is not to be confused with Domain Messages.
 *
 * Caution: The message is intended to be displayed in the UI layer,
 * avoid disclosing hidden system details (like exception stacks).
 *
 * ServiceMessages are not system exceptions, they are informational messages.
 * Use the `systemMessage` property to share details for debugging.
 */
class ServiceMessage
{
    /** @var string */
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

    /** @var string[] */
    public $translatedParameters = [];

    /** @var string|null */
    public $systemMessage;

    /**
     * @param string      $type              Type of the message (error, warning, notice)
     * @param string|null $messageTemplate   The template for the message
     * @param array       $messageParameters The parameters that should be
     *                                       substituted in the message template
     * @param string|null $systemMessage     Untranslated information about the system (mainly for debugging)
     */
    protected function __construct(string $type, ?string $messageTemplate, array $messageParameters = [], ?string $systemMessage = null)
    {
        $this->type              = $type;
        $this->messageTemplate   = $messageTemplate;
        $this->messageParameters = $messageParameters;
        $this->systemMessage     = $systemMessage;
    }

    /**
     * @return static
     */
    public function withPlural(?int $messagePluralization)
    {
        $this->messagePluralization = $messagePluralization;

        return $this;
    }

    /**
     * @param array $translatedParameters An array of parameter names that need
     *                                    to be translated prior to their usage
     *
     * @return static
     */
    public function translateParameters(array $translatedParameters)
    {
        $this->translatedParameters = $translatedParameters;

        return $this;
    }

    public function __toString(): string
    {
        return $this->systemMessage ?? '';
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
