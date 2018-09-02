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

final class NullSender implements Sender
{
    /**
     * {@inheritdoc}
     */
    public function send(string $template, array $recipients, array $variables): void
    {
        // no-op
    }

    /**
     * {@inheritdoc}
     */
    public function sendWithAttachments(string $template, array $recipients, array $variables, array $attachments): void
    {
        // no-op
    }
}
