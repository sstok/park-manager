<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use Doctrine\Persistence\ObjectManager;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;
use Rollerworks\Component\X509Validator\CA as CAInfo;
use Rollerworks\Component\X509Validator\CAResolverImpl;
use Rollerworks\Component\X509Validator\X509DataExtractor;

/**
 * @final
 */
class CAResolver
{
    private X509DataExtractor $extractor;
    private CAResolverImpl $caResolver;

    /**
     * @param ObjectManager<CA> $objectManager
     */
    public function __construct(private ObjectManager $objectManager)
    {
        $this->extractor = new X509DataExtractor();
        $this->caResolver = new CAResolverImpl();
    }

    /**
     * @param array<string, string> $caList
     */
    public function resolve(string $certificate, array $caList): ?CA
    {
        $ca = $this->caResolver->resolve($certificate, $caList);

        if ($ca === null) {
            return null;
        }

        return $this->objectManager->find(CA::class, CA::getHash($ca->contents)) ?? $this->resolveCA($ca);
    }

    private function resolveCA(?CAInfo $ca): CA
    {
        /** @var array<int, string> $tree */
        $tree = [];

        while ($ca !== null) {
            $tree[] = $ca->contents;
            $ca = $ca->parent;
        }

        $parent = null;

        foreach (array_reverse($tree) as $contents) {
            $caEntity = $this->objectManager->find(CA::class, CA::getHash($contents));

            if ($caEntity === null) {
                $x509Info = $this->extractor->extractRawData($contents, '', true);
                $caEntity = new CA($contents, $x509Info->allFields, $parent);
                $this->objectManager->persist($caEntity);
            }

            $parent = $caEntity;
        }

        return $caEntity;
    }
}
