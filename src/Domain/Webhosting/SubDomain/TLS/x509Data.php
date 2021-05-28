<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain\TLS;

use Assert\Assertion;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

/** @internal */
trait x509Data
{
    /**
     * @ORM\ManyToOne(targetEntity=CA::class)
     * @ORM\JoinColumn(name="ca", nullable=true, referencedColumnName="hash", onDelete="RESTRICT")
     */
    public ?CA $ca = null;

    /**
     * SHA-256 locator id.
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=65)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private string $hash;

    /**
     * @ORM\Column(type="binary")
     *
     * @var resource|string
     */
    private $contents;

    /**
     * @ORM\Column(type="binary")
     *
     * @var resource|string
     */
    private $publicKey;

    /**
     * @ORM\Column(type="json")
     *
     * @var array<string, mixed>
     */
    private array $rawFields = [];

    private ?string $publicKeyString = null;
    private ?string $contentsString = null;

    private function __construct(string $contents, array $rawFields, ?CA $ca = null)
    {
        Assertion::keyExists($rawFields, '_pubKey');
        Assertion::keyExists($rawFields, 'subject');
        Assertion::keyExists($rawFields, 'issuer');

        $this->contents = $contents;
        $this->contentsString = $contents;
        $this->publicKey = $rawFields['_pubKey'];
        $this->publicKeyString = $rawFields['_pubKey'];

        // Public key is stored as binary
        unset($rawFields['_pubKey']);

        $this->hash = self::getHash($contents);
        $this->rawFields = $rawFields;
        $this->ca = $ca;
    }

    public function getId(): string
    {
        return $this->hash;
    }

    public static function getHash(string $contents): string
    {
        return hash('sha256', $contents);
    }

    public function getPublicKey(): string
    {
        if (! isset($this->publicKeyString)) {
            if (! \is_resource($this->publicKey)) {
                throw new \InvalidArgumentException('PublicKey resource was not initialized.');
            }

            $this->publicKeyString = stream_get_contents($this->publicKey);
        }

        return $this->publicKeyString;
    }

    public function getContents(): string
    {
        if (! isset($this->contentsString)) {
            if (! \is_resource($this->contents)) {
                throw new \InvalidArgumentException('Contents resource was not initialized.');
            }

            $this->contentsString = stream_get_contents($this->contents);
        }

        return $this->contentsString;
    }

    public function getCommonName(): string
    {
        return $this->rawFields['_commonName'];
    }

    public function getSignatureAlgorithm(): string
    {
        return $this->rawFields['_signatureAlgorithm'];
    }

    public function getFingerprint(): string
    {
        return $this->rawFields['_fingerprint'];
    }

    public function isExpired(): bool
    {
        return $this->expirationDate()->isPast();
    }

    public function expirationDate(): Carbon
    {
        return Carbon::rawParse($this->rawFields['_validTo']);
    }

    public function getIssuer(): array
    {
        return $this->rawFields['issuer'];
    }

    public function daysUntilExpirationDate(): int
    {
        return (int) Carbon::now()->diff($this->expirationDate())->format('%r%a');
    }

    public function isValidUntil(Carbon $carbon): bool
    {
        return $this->expirationDate()->gte($carbon);
    }

    public function isValid(): bool
    {
        return $this->validFromDate()->betweenIncluded($this->validFromDate(), $this->expirationDate());
    }

    public function validFromDate(): Carbon
    {
        return Carbon::rawParse($this->rawFields['_validFrom']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawFields(): array
    {
        return $this->rawFields;
    }
}
