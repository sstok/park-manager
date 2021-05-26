<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Subdomain;

use ParkManager\Application\Command\Webhosting\SubDomain\AddSubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;
use ParkManager\Infrastructure\Validator\Constraints\X509CertificateBundle;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AddSubDomainType extends SubDomainType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('command_factory', static function (array $form) {
            $command = new AddSubDomain(
                SubDomainNameId::create(),
                $form['root_domain']->id,
                $form['name'],
                $form['homedir'],
                $form['config'] ?? [] // To be done in the future
            );

            if ($form['tlsInfo'] !== null) {
                $tlsInformation = $form['tlsInfo'];
                \assert($tlsInformation instanceof X509CertificateBundle);
                $command->andTLSInformation($tlsInformation->certificate, $tlsInformation->privateKey, $tlsInformation->caList);
            }

            return $command;
        });
    }
}
