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

namespace ParkManager\Common\Domain;

/**
 * A ConstraintFailed is thrown when a single domain constraint
 * is violated. This can relate to a name that's already in use
 * or setting attributes not supported by the Model.
 *
 * Caution: The message will bubble-up back to the higher system.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class ConstraintFailed extends \InvalidArgumentException
{
    /**
     * @var string
     */
    public $messageId;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @var int|null
     */
    public $plural;

    /**
     * Constructor.
     *
     * @param string   $messageId  Message-id for rendering
     * @param array    $parameters Parameters for message-id
     * @param int|null $plural     Indicates whether the message is plural
     *                             and what the plural count is
     */
    public function __construct(string $messageId, array $parameters = [], int $plural = null)
    {
        parent::__construct($messageId, 1);

        $this->messageId = $messageId;
        $this->parameters = $parameters;
        $this->plural = $plural;
    }
}
