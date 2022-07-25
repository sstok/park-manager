<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use Assert\Assertion;
use Assert\InvalidArgumentException as AssertionInvalidArgumentException;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\Table;
use DomainException;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\Exception\CannotTransferSystemWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\InvalidStatus;

#[Entity]
#[Table(name: 'space')]
class Space
{
    use TimestampableTrait;

    #[Column(name: 'expires_on', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $expirationDate = null;

    #[Column(name: 'marked_for_removal', type: 'boolean', nullable: true)]
    public bool $markedForRemoval = false;

    /**
     * READ-ONLY: The allocated size for web storage.
     *
     * This value must not exceed the Constraints.$storageSize value,
     * and must not be more than the total size allocated to mail storage.
     *
     * If this value is NULL the size is not allocated yet, and must be ignored
     * for any calculations.
     */
    #[Column(type: 'byte_size', nullable: true)]
    public ?ByteSize $webQuota = null;

    /**
     * This is a static value updated by `DomainName::transferToSpace(primary: true)`
     * meant for display purposes only.
     */
    #[Embedded(class: DomainNamePair::class, columnPrefix: 'primary_domain_')]
    public DomainNamePair $primaryDomainLabel;

    #[Column(name: 'setup_status', enumType: SpaceSetupStatus::class)]
    public SpaceSetupStatus $setupStatus = SpaceSetupStatus::REGISTERED;

    #[Column(name: 'access_suspended', nullable: true, enumType: SuspensionLevel::class)]
    public ?SuspensionLevel $accessSuspended = null;

    /**
     * @var Collection<int, AccessSuspensionLog>
     */
    #[OneToMany(targetEntity: AccessSuspensionLog::class, mappedBy: 'space', cascade: ['PERSIST'])]
    #[OrderBy(['timestamp' => 'ASC'])]
    private Collection $suspensions;

    #[Embedded(class: SystemRegistration::class, columnPrefix: 'system_registration_')]
    public ?SystemRegistration $systemRegistration = null;

    private function __construct(
        #[Id]
        #[Column(type: 'park_manager_webhosting_space_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public SpaceId $id,

        #[ManyToOne(targetEntity: Owner::class)]
        #[JoinColumn(name: 'owner', referencedColumnName: 'owner_id', onDelete: 'RESTRICT')]
        public Owner $owner,

        #[Embedded(class: Constraints::class, columnPrefix: 'constraint_')]
        public Constraints $constraints,

        #[ManyToOne(targetEntity: Plan::class)]
        #[JoinColumn(name: 'plan_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
        public ?Plan $plan = null
    ) {
        $this->suspensions = new ArrayCollection();
    }

    public static function register(SpaceId $id, Owner $owner, Plan $plan): self
    {
        return new self($id, $owner, $plan->constraints, $plan);
    }

    public static function registerWithCustomConstraints(SpaceId $id, Owner $owner, Constraints $constraints): self
    {
        return new self($id, $owner, constraints: $constraints);
    }

    /**
     * Change the webhosting Plan assignment,
     * withing using the actual Constraints of the set.
     */
    public function assignPlan(Plan $plan): void
    {
        $this->plan = $plan;
    }

    /**
     * Change the webhosting Plan assignment,
     * and use the Constraints of the assigned plan.
     */
    public function assignPlanWithConstraints(Plan $plan, Constraints $constraints): void
    {
        $this->plan = $plan;

        if (! $this->constraints->equals($constraints)) {
            $this->constraints = $constraints;
        }
    }

    /**
     * Change the webhosting space Constraints.
     *
     * This removes the plan's assignment and makes the space's
     * Constraints exclusive.
     */
    public function assignCustomConstraints(Constraints $constraints): void
    {
        $this->plan = null;

        if (! $this->constraints->equals($constraints)) {
            $this->constraints = $constraints;
        }
    }

    /**
     * Resetting to NULL is not possible, use {ByteSize::Inf()} instead.
     *
     * @throws AssertionInvalidArgumentException
     */
    public function setWebQuota(ByteSize $size): void
    {
        Assertion::false($size->greaterThan($this->constraints->storageSize), 'WebSpace quota cannot be greater than the total storage size.', 'webQuota');

        if ($size->equals($this->webQuota)) {
            return;
        }

        $this->webQuota = $size;
    }

    public function transferToOwner(Owner $owner): void
    {
        if ($this->owner->isOrganization()) {
            /** @var Organization $ownerEntity */
            $ownerEntity = $this->owner->getLinkedEntity();

            if ($ownerEntity->id->equals(OrganizationId::fromString(OrganizationId::SYSTEM_APP))) {
                throw CannotTransferSystemWebhostingSpace::withId($this->id);
            }
        }

        $this->owner = $owner;
    }

    /**
     * Set the webhosting space to expire (be removed) on a specific
     * datetime.
     *
     * Note: There is no promise the webhosting space will in fact
     * be removed on the specified date. This depends on other subsystems.
     */
    public function markExpirationDate(DateTimeImmutable $data): void
    {
        Assertion::false(Carbon::instance($data)->isPast(), 'Expiration date cannot be in the past.', 'expirationDate');

        $this->expirationDate = $data;
    }

    /**
     * Remove the webhosting space's expiration date (if any).
     */
    public function removeExpirationDate(): void
    {
        $this->expirationDate = null;
    }

    public function isExpired(?DateTimeImmutable $current = null): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }

        return $this->expirationDate->getTimestamp() <= ($current ?? new Carbon())->getTimestamp();
    }

    /**
     * Mark the webhosting space for removal (this cannot be undone!).
     */
    public function markForRemoval(): void
    {
        $this->markedForRemoval = true;
    }

    public function isMarkedForRemoval(): bool
    {
        return $this->markedForRemoval;
    }

    public function setPrimaryDomainLabel(DomainNamePair $primaryDomainLabel): void
    {
        $this->primaryDomainLabel = $primaryDomainLabel;
    }

    public function assignSetupStatus(SpaceSetupStatus $newStatus): void
    {
        if ($this->setupStatus === $newStatus) {
            return;
        }

        SpaceSetupStatus::validateNewStatus($this->setupStatus, $newStatus);

        $this->setupStatus = $newStatus;
    }

    public function getStatusAsString(): string
    {
        if ($this->markedForRemoval) {
            return 'marked_for_removal';
        }

        if ($this->accessSuspended !== null) {
            return 'suspended';
        }

        return $this->setupStatus->label();
    }

    public function suspendAccess(SuspensionLevel $level): void
    {
        if ($this->setupStatus !== SpaceSetupStatus::READY) {
            throw new DomainException('Cannot set suspension level when Space has not completed initialization yet.');
        }

        if (SuspensionLevel::equalsTo($this->accessSuspended, $level)) {
            return;
        }

        $this->accessSuspended = $level;
        $this->suspensions->add(new AccessSuspensionLog($this, $level, CarbonImmutable::now()));
    }

    public function removeAccessSuspension(): void
    {
        if ($this->accessSuspended === null) {
            return;
        }

        $this->suspensions->add(new AccessSuspensionLog($this, null, CarbonImmutable::now()));
        $this->accessSuspended = null;
    }

    /**
     * @return Collection<int, AccessSuspensionLog>
     */
    public function getSuspensions(): Collection
    {
        return $this->suspensions;
    }

    /**
     * @param array<int, int> $userGroups
     */
    public function setupWith(int $userId, array $userGroups, string $homeDir): void
    {
        if ($this->setupStatus !== SpaceSetupStatus::GETTING_INITIALIZED) {
            throw new InvalidStatus(sprintf('Cannot Setup Space when status is not "Getting_Initialized", current status is "%s".', $this->setupStatus->label()));
        }

        $this->systemRegistration = new SystemRegistration($userId, $userGroups, $homeDir);
        $this->setupStatus = SpaceSetupStatus::READY;
    }
}
