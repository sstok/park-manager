<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use Carbon\CarbonInterface;
use Ocsp\CertificateInfo;
use Ocsp\CertificateLoader;
use Ocsp\Exception\Exception as OcspException;
use Ocsp\Ocsp;
use ParkManager\Application\Service\PdpManager as PublicSuffixManager;
use ParkManager\Application\Service\TLS\Violation\CertificateIsExpired;
use ParkManager\Application\Service\TLS\Violation\CertificateIsRevoked;
use ParkManager\Application\Service\TLS\Violation\GlobalWildcard;
use ParkManager\Application\Service\TLS\Violation\UnsupportedDomain;
use ParkManager\Application\Service\TLS\Violation\UnsupportedPurpose;
use ParkManager\Application\Service\TLS\Violation\WeakSignatureAlgorithm;
use ParkManager\Domain\Webhosting\SubDomain\TLS\CA;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @final
 */
class CertificateValidator
{
    public const PURPOSE_SMIME = 'S/MIME';
    public const PURPOSE_SMIME_SIGNING = 'S/MIME signing';
    public const PURPOSE_SMIME_ENCRYPTION = 'S/MIME encryption';

    public const PURPOSE_SSL_CLIENT = 'SSL client';
    public const PURPOSE_SSL_SERVER = 'SSL server';

    private CAResolver $caResolver;
    private PublicSuffixManager $suffixManager;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private Ocsp $ocsp;
    private X509DataExtractor $extractor;

    public function __construct(CAResolver $CAResolver, PublicSuffixManager $suffixManager, HttpClientInterface $httpClient, LoggerInterface $logger, ?Ocsp $ocsp = null, ?X509DataExtractor $dataExtractor = null)
    {
        $this->caResolver = $CAResolver;
        $this->suffixManager = $suffixManager;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->ocsp = $ocsp ?? new Ocsp();

        $this->extractor = $dataExtractor ?? new X509DataExtractor();
    }

    /**
     * @throws Violation
     */
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
        return $this->extractor->extractRawData($contents);
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
        $rules = $this->suffixManager->getPublicSuffixList();

        foreach ($domains as $domain) {
            if (! \str_contains($domain, '*')) {
                continue;
            }

            if ($domain === '*') {
                throw new GlobalWildcard($domain, '*');
            }

            $domainInfo = $rules->resolve($domain);

            if (! $domainInfo->suffix()->isKnown()) {
                return;
            }

            $publicSuffix = $domainInfo->suffix()->toString();

            if (\rtrim(\mb_substr($domainInfo->toString(), 0, -\mb_strlen($publicSuffix)), '.') === '*') {
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
        $requiredPurposes = \array_fill_keys($requiredPurpose, true);

        if (isset($requiredPurposes[self::PURPOSE_SMIME])) {
            unset($requiredPurposes[self::PURPOSE_SMIME]);

            $requiredPurposes['S/MIME signing'] = true;
            $requiredPurposes['S/MIME encryption'] = true;
        }

        $purposes = [];

        foreach ($this->extractRawData($certificate)['purposes'] as $purpose) {
            $purposes[$purpose[2]] = $purpose[0];
        }

        foreach ($requiredPurposes as $requirement => $v) {
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
