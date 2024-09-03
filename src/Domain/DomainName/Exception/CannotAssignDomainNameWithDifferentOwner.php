<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CannotAssignDomainNameWithDifferentOwner extends \DomainException implements DomainError, TranslatableInterface
{
    public DomainNamePair $domainName;
    public ?SpaceId $current = null;
    public SpaceId $new;

    public static function toSpace(DomainNamePair $domainName, SpaceId $space): self
    {
        $instance = new self(
            sprintf(
                'Domain name "%s" does not have the same owner as Space "%s".',
                $domainName->toString(),
                $space->toString()
            )
        );

        $instance->domainName = $domainName;
        $instance->new = $space;

        return $instance;
    }

    public static function fromSpace(DomainNamePair $domainName, SpaceId $current, SpaceId $new): self
    {
        $instance = new self(
            sprintf(
                'Domain name "%s" of Space %s does not have the same owner as Space %s.',
                $domainName->toString(),
                $current->toString(),
                $new->toString()
            )
        );

        $instance->domainName = $domainName;
        $instance->current = $current;
        $instance->new = $new;

        return $instance;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        if ($this->current) {
            return $translator->trans(
                'domain_name.cannot_assign_domain_name_with_different_space_owner',
                $this->getTranslationArgs(),
                'validators',
                $locale,
            );
        }

        return $translator->trans(
            'domain_name.cannot_assign_domain_name_with_different_owner',
            $this->getTranslationArgs(),
            'validators',
            $locale,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'current_space' => $this->current ? new EntityLink($this->current) : null,
            'new_space' => new EntityLink($this->new),
        ];
    }

    public function getPublicMessage(): string
    {
        if ($this->current) {
            return 'DomainName "{domainName}" of Space {current} does not have the same owner as Space {new}.';
        }

        return 'DomainName "{domainName}" does not have the same owner as Space "{new}".';
    }
}
