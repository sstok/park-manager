<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Ocsp\CertificateInfo;
use Ocsp\CertificateLoader;
use Ocsp\Exception\Exception as OcspException;
use Ocsp\Ocsp;
use ParkManager\Application\Service\TLS\Violation\CertificateIsExpired;
use ParkManager\Application\Service\TLS\Violation\CertificateIsRevoked;
use ParkManager\Application\Service\TLS\Violation\GlobalWildcard;
use ParkManager\Application\Service\TLS\Violation\UnprocessablePEM;
use ParkManager\Application\Service\TLS\Violation\UnsupportedDomain;
use ParkManager\Application\Service\TLS\Violation\UnsupportedPurpose;
use ParkManager\Application\Service\TLS\Violation\WeakSignatureAlgorithm;
use ParkManager\Domain\DomainName\TLS\CA;
use Pdp\Manager as PublicSuffixManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @final
 */
class CertificateValidator
{
    public const PURPOSE_SMIME = 'S/MIME';
    public const PURPOSE_SSL_CLIENT = 'SSL client';
    public const PURPOSE_SSL_SERVER = 'SSL server';

    private CAResolver $caResolver;
    private PublicSuffixManager $suffixManager;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    private ?string $hash = null;
    private ?array $fields = null;

    private Ocsp $ocsp;

    public function __construct(CAResolver $CAResolver, PublicSuffixManager $suffixManager, HttpClientInterface $httpClient, LoggerInterface $logger, ?Ocsp $ocsp = null)
    {
        $this->caResolver = $CAResolver;
        $this->suffixManager = $suffixManager;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->ocsp = $ocsp ?? new Ocsp();
    }

    public function validateCertificate(string $certificate, array $caList = []): void
    {
        $data = $this->extractRawData($certificate);

        $this->validateNotExpired($data['_validTo']);
        $this->validateSignatureAlgorithm($data['signatureTypeLN']);
        $this->validateDomainsWildcard($data['_domains']);

        $ca = $this->caResolver->resolve($certificate, $caList);

        // If there is no CA, no point in validating the OCSP
        if ($ca === null) {
            return;
        }

        $this->validateOCSPStatus($certificate, $data, $ca);
    }

    /**
     * @return array<string,mixed>
     */
    protected function extractRawData(string $contents): array
    {
        $hash = \hash('sha256', $contents);

        // The same cert information is likely to be validated multiple times
        // So keep a local cache to speed-up the parsing process a little.
        if ($hash === $this->hash && $this->fields !== null) {
            return $this->fields;
        }

        $x509Read = @\openssl_x509_read($contents);

        if ($x509Read === false) {
            throw new UnprocessablePEM('', $contents);
        }

        // @codeCoverageIgnoreStart
        $fields = @\openssl_x509_parse($x509Read, false);

        if ($fields === false) {
            throw new UnprocessablePEM('', $contents);
        }
        // @codeCoverageIgnoreEnd

        $fields += [
            '_commonName' => \trim($fields['subject']['commonName']),
            '_altNames' => $this->getAltNames($fields),
            '_validTo' => Carbon::rawParse($fields['validTo_time_t']),
            '_validFrom' => Carbon::rawParse($fields['validFrom_time_t']),
        ];
        $fields['_domains'] = $fields['_altNames'] + [$fields['_commonName']];

        $this->hash = $hash;
        $this->fields = $fields;

        return $fields;
    }

    /**
     * @param array<string,mixed> $rawData
     *
     * @return array<int,string>
     */
    private function getAltNames(array $rawData): array
    {
        if (! isset($rawData['extensions']['subjectAltName'])) {
            return [];
        }

        return \array_map(
            static fn ($item) => \explode(':', \trim($item), 2)[1],
            \array_filter(
                \explode(',', $rawData['extensions']['subjectAltName']),
                static fn ($item) => \mb_strpos($item, ':') !== false
            )
        );
    }

    private function validateNotExpired(CarbonInterface $validTo): void
    {
        if ($validTo->isPast()) {
            throw new CertificateIsExpired($validTo);
        }
    }

    private function validateSignatureAlgorithm(string $signatureType): void
    {
        $normSignatureType = \mb_strtolower(\preg_replace('/(WithRSAEncryption$)|(^ecdsa-with-)/i', '', $signatureType));

        // While sha224 is considered the same as sha256 it's no longer part of TLS 1.3
        if (\in_array($normSignatureType, ['none', 'md2', 'md5', 'sha1', 'sha224'], true)) {
            throw new WeakSignatureAlgorithm('SHA256', $signatureType);
        }
    }

    private function validateDomainsWildcard(array $domains): void
    {
        $rules = $this->suffixManager->getRules();

        foreach ($domains as $domain) {
            if (\mb_strpos($domain, '*') === false) {
                continue;
            }

            if ($domain === '*') {
                throw new GlobalWildcard($domain, '*');
            }

            $domainInfo = $rules->resolve($domain);

            if (! $domainInfo->isKnown()) {
                return;
            }

            $publicSuffix = $domainInfo->getPublicSuffix() ?? '';

            if (\rtrim(\mb_substr($domainInfo->getContent() ?? '', 0, -\mb_strlen($publicSuffix)), '.') === '*') {
                throw new GlobalWildcard($domain, $publicSuffix);
            }
        }
    }

    private function validateOCSPStatus(string $certificate, array $data, CA $ca): void
    {
        static $certificateLoader, $certificateInfo;

        if (! $certificateLoader) {
            $certificateLoader = new CertificateLoader();
            $certificateInfo = new CertificateInfo();
        }

        try {
            $certificateSeq = $certificateLoader->fromString($certificate);
            $ocspResponderUrl = $certificateInfo->extractOcspResponderUrl($certificateSeq);

            if ($ocspResponderUrl === '') {
                $this->logger->debug('No OCSP found for certificate.', $data);

                return;
            }

            $issuerCertificate = $certificateLoader->fromString($ca->getContents());
            $requestInfo = $certificateInfo->extractRequestInfo($certificateSeq, $issuerCertificate);

            // Build the raw body to be sent to the OCSP Responder URL
            $requestBody = $this->ocsp->buildOcspRequestBodySingle($requestInfo);

            $response = $this->httpClient->request('POST', $ocspResponderUrl, [
                'body' => $requestBody,
                'headers' => [
                    'Content-Type' => Ocsp::OCSP_REQUEST_MEDIATYPE,
                ],
            ]);

            if ($response->getStatusCode() !== 200 || $response->getHeaders()['content-type'][0] !== Ocsp::OCSP_RESPONSE_MEDIATYPE) {
                $this->logger->warning('Unable to check OCSP status.', ['response' => $response]);

                return;
            }

            $ocspResponse = $this->ocsp->decodeOcspResponseSingle($response->getContent());

            if ($ocspResponse->isRevoked()) {
                throw new CertificateIsRevoked($ocspResponse->getRevokedOn(), $ocspResponse->getRevocationReason(), $ocspResponse->getCertificateSerialNumber());
            }
        } catch (OcspException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function validateCertificatePurpose(string $certificate, string ...$requiredPurpose): void
    {
        $requiredPurpose = \array_fill_keys($requiredPurpose, true);

        if (isset($requiredPurpose[self::PURPOSE_SMIME])) {
            unset($requiredPurpose[self::PURPOSE_SMIME]);

            $requiredPurpose['S/MIME signing'] = true;
            $requiredPurpose['S/MIME encryption'] = true;
        }

        $purposes = [];

        foreach ($this->extractRawData($certificate)['purposes'] as $purpose) {
            $purposes[$purpose[2]] = $purpose[0];
        }

        foreach ($requiredPurpose as $requirement => $v) {
            if (($purposes[$requirement] ?? false) === false) {
                throw new UnsupportedPurpose($requirement);
            }
        }
    }

    public function validateCertificateHost(string $certificate, string $hostPattern): void
    {
        $this->validateCertificatePurpose($certificate, self::PURPOSE_SSL_SERVER);

        $data = $this->extractRawData($certificate);

        foreach ($data['_domains'] as $value) {
            if (\preg_match('#^' . \str_replace(['.', '*'], ['\.', '[^.]*'], $value) . '$#', $hostPattern)) {
                return;
            }
        }

        throw new UnsupportedDomain($hostPattern, ...$data['_domains']);
    }

    public function validateCertificateSupport(string $certificate, callable $validator): void
    {
        $data = $this->extractRawData($certificate);
        $validator($data, $certificate, $this);
    }
}
