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

namespace ParkManager\Component\Mailer;

interface Sender
{
    /**
     * @param string  $template   Template name (and path)
     * @param array   $recipients Either a string (e-mail address)
     *                            or e-mail address => name
     * @param mixed[] $variables  Variables for the template
     */
    public function send(string $template, array $recipients, array $variables): void;

    /**
     * @param string              $template    Template name (and path)
     * @param string[]|array[]    $recipients  Either a string (e-mail address)
     *                                         or "e-mail address" => name
     * @param mixed[]             $variables   Variables for the template
     * @param string[]|resource[] $attachments Filename => absolute path or
     *                                         PHP stream resource (read-only)
     */
    public function sendWithAttachments(string $template, array $recipients, array $variables, array $attachments): void;
}
