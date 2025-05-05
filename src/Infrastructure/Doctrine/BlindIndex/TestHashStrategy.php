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

#[AutoconfigureTag(DoctrineBlindIndexStrategyPass::TAG_NAME, ['strategy' => 'test_hash'])]
final class TestHashStrategy implements BlindIndexStrategy
{
    public function configureOptionsResolver(OptionsResolver $resolver): void
    {
        // Noop
    }

    public function getSchema(BlindIndexMetadata $mapping): Column
    {
        return (new Column(type: 'string', options: ['notNull' => false]));
    }

    public function getValue(BlindIndexMetadata $mapping, mixed $value, Connection $connection): mixed
    {
        return sprintf('<TEST>%s</TEST>', $value);
    }

    public function getColumnType(array $options): string
    {
        return 'string';
    }
}
