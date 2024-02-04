<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Ftp;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use IPLib\Address\AddressInterface as IpAddress;
use IPLib\Range\RangeInterface as IpRange;
use Lifthill\Component\Common\Domain\Attribute\Entity as DomainEntity;
use ParkManager\Domain\Webhosting\Space\Space;

/**
 * An AccessRule applies for either a single FtpUser (user-level),
 * or all FTP users within in a space (space-level).
 *
 * Rules are checked user-level first, and space-level second.
 * Any explicit matching user-rule prevails over all space-level rules.
 *
 * Note: When there is at least one enabled (per level) explicit allow-rule
 * all blocking rules are ignored.
 */
#[Entity]
#[Table(name: 'ftp_access_rule')]
#[DomainEntity]
class AccessRule
{
    #[Column(type: 'boolean')]
    public bool $enabled = true;

    protected function __construct(
        #[Id]
        #[Column(type: 'park_manager_ftp_access_rule_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public AccessRuleId $id,

        #[ManyToOne(targetEntity: Space::class)]
        #[JoinColumn(name: 'space', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
        public Space $space,

        /**
         * When null this rule applies to all FTP users within this Space (space-level).
         *
         * This cannot be changed later-on. Remove this rule instead, and create a new one.
         */
        #[ManyToOne(targetEntity: FtpUser::class)]
        #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
        public ?FtpUser $user,

        /**
         * Either a single IP(v4 or v6) address or a CIDR subnet range.
         */
        #[Column(name: 'ip_address', type: 'cidr')]
        public IpAddress | IpRange $address,

        #[Column(name: 'strategy', enumType: AccessRuleStrategy::class)]
        public AccessRuleStrategy $strategy,
    ) {}

    public static function createForSpace(AccessRuleId $id, Space $space, IpAddress | IpRange $address, AccessRuleStrategy $strategy = AccessRuleStrategy::DENY): self
    {
        return new self($id, $space, null, $address, $strategy);
    }

    public static function createForUser(AccessRuleId $id, FtpUser $user, IpAddress | IpRange $address, AccessRuleStrategy $strategy = AccessRuleStrategy::DENY): self
    {
        return new self($id, $user->space, $user, $address, $strategy);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
