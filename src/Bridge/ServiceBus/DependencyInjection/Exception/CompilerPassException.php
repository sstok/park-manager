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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class CompilerPassException extends RuntimeException
{
    public static function toManyTags(string $serviceId, string $tagName): self
    {
        return new self(sprintf('Service "%s" can only have one "%s" tag', $serviceId, $tagName));
    }

    public static function unknownClass(string $className, string $serviceId, string $tagName): self
    {
        return new self(sprintf(
            'Service "%s" has been tagged as "%s", but its class "%s" does not exist.',
            $serviceId,
            $tagName,
            $className
        ));
    }

    public static function duplicateMessageHandler(string $messageName, string $newId, string $existingId): self
    {
        return new self(sprintf('Cannot overwrite current handler "%s" with "%s" for message "%s".', $existingId, $newId, $messageName));
    }

    public static function cannotDetectSupported(string $serviceId): self
    {
        return new self(
            sprintf(
                'Unable to determine supported message for handler in "%s".'.
                'Either provide an "message" attribute for the tag or make sure __invoke() '.
                'exists, is public (not an abstract) and has exactly one required object argument at position 0.',
                $serviceId
            )
        );
    }
}
