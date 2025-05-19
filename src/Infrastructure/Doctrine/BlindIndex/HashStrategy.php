<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\BlindIndex;

use Doctrine\DBAL\Connection;
use Lifthill\Bridge\Doctrine\Encryption\BlindIndex\BlindIndexStrategy;
use Lifthill\Bridge\Doctrine\Encryption\BlindIndex\Column;
use Lifthill\Bridge\Doctrine\Encryption\BlindIndex\Metadata\BlindIndexMetadata;
use Lifthill\Bundle\CoreBundle\DependencyInjection\Compiler\DoctrineBlindIndexStrategyPass;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AutoconfigureTag(DoctrineBlindIndexStrategyPass::TAG_NAME, ['strategy' => 'hash'])]
final class HashStrategy implements BlindIndexStrategy
{
    public function configureOptionsResolver(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('hash_algorithm', 'sha1')
            ->setAllowedValues('hash_algorithm', hash_algos());
    }

    public function getSchema(BlindIndexMetadata $mapping): Column
    {
        return (new Column(type: 'string', options: ['length' => mb_strlen(hash($mapping->options['hash_algorithm'], 'test'), '8bit')]))
            ->indexed();
    }

    public function getValue(BlindIndexMetadata $mapping, mixed $value, Connection $connection): mixed
    {
        return hash($mapping->options['hash_algorithm'], $value);
    }

    public function getColumnType(array $options): string
    {
        return 'string';
    }
}
