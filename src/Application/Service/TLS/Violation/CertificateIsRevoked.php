<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use DateTimeInterface;
use Ocsp\Response;
use ParkManager\Application\Service\TLS\Violation;
use ParkManager\Domain\Translation\TranslatableMessage;

final class CertificateIsRevoked extends Violation
{
    // https://security.stackexchange.com/questions/174327/definitions-for-crl-reasons
    //
    // - unspecified: can be used to revoke certificates for reasons other than the specific codes.
    // - keyCompromise: is used in revoking an end-entity certificate; it indicates that it is known or suspected that the subject's private key, or other aspects of the subject validated in the certificate, have been compromised.
    // - cACompromise: is used in revoking a CA-certificate; it indicates that it is known or suspected that the subject's private key, or other aspects of the subject validated in the certificate, have been compromised.
    // - affiliationChanged: indicates that the subject's name or other information in the certificate has been modified but there is no cause to suspect that the private key has been compromised.
    // - superseded: indicates that the certificate has been superseded but there is no cause to suspect that the private key has been compromised.
    // - cessationOfOperation: indicates that the certificate is no longer needed for the purpose for which it was issued but there is no cause to suspect that the private key has been compromised.
    // - privilegeWithdrawn: indicates that a certificate (public-key or attribute certificate) was revoked because a privilege contained within that certificate has been withdrawn.
    // - aACompromise: indicates that it is known or suspected that aspects of the AA validated in the attribute certificate have been compromised.

    private const REVOCATION_REASON = [
        Response::REVOCATIONREASON_UNSPECIFIED => 'unspecified',
        Response::REVOCATIONREASON_KEYCOMPROMISE => 'key_compromise',
        Response::REVOCATIONREASON_CACOMPROMISE => 'ca_compromise',
        Response::REVOCATIONREASON_AFFILIATIONCHANGED => 'affiliation_changed',
        Response::REVOCATIONREASON_SUPERSEDED => 'superseded',
        Response::REVOCATIONREASON_CESSATIONOFOPERATION => 'cessation_of_operation',
        Response::REVOCATIONREASON_CERTIFICATEHOLD => 'certificate_hold',
        Response::REVOCATIONREASON_REMOVEFROMCRL => 'remove_from_crl',
        Response::REVOCATIONREASON_PRIVILEGEWITHDRAWN => 'privilege_withdrawn',
        Response::REVOCATIONREASON_AACOMPROMISE => 'AA compromise',
    ];

    private ?DateTimeInterface $revokedOn;
    private ?int $reason;
    private string $serial;

    public function __construct(?DateTimeInterface $revokedOn, ?int $reason, string $serialNumber)
    {
        parent::__construct(
            sprintf(
                'The certificate with serialNumber "%s" is revoked on "%s" due to reason "%s".',
                $serialNumber,
                $revokedOn ? $revokedOn->format(\DATE_ISO8601) : 'no-date',
                (string) $reason
            )
        );

        $this->revokedOn = $revokedOn;
        $this->reason = $reason;
        $this->serial = $serialNumber;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.certificate_is_revoked';
    }

    public function getParameters(): array
    {
        return [
            'revoked_on' => $this->revokedOn,
            'reason_code' => (self::REVOCATION_REASON[$this->reason] ?? 'unspecified'),
            'reason' => new TranslatableMessage('tls.revocation_reason.' . (self::REVOCATION_REASON[$this->reason] ?? 'unspecified'), domain: 'messages'),
            'serial' => $this->serial,
        ];
    }
}
