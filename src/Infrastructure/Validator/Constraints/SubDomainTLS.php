<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * @Annotation
 */
final class SubDomainTLS extends Compound
{
    public $hostPattern;

    public function getRequiredOptions(): array
    {
        return ['hostPattern'];
    }

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotNull(),
                new X509Certificate(),
                new X509Purpose(X509Purpose::PURPOSE_SSL_SERVER),
                new X509HostnamePattern($options['hostPattern']),
                new X509KeyPair(),
            ]),
        ];
    }
}
