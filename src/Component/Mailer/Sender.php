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

namespace ParkManager\Component\Mailer;

interface Sender
{
    /**
     * @param string $template   Template name (and path)
     * @param array  $recipients Either a string (e-mail address)
     *                           or e-mail address => name
     * @param array  $variables  Variables for the template
     */
    public function send(string $template, array $recipients, array $variables): void;

    /**
     * @param string              $template    Template name (and path)
     * @param array               $recipients  Either a string (e-mail address)
     *                                         or "e-mail address" => name
     * @param array               $variables   Variables for the template
     * @param string[]|resource[] $attachments Filename => absolute path or
     *                                         PHP stream resource (read-only)
     */
    public function sendWithAttachments(string $template, array $recipients, array $variables, array $attachments): void;
}
