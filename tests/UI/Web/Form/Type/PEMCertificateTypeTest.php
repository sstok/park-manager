<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use Lifthill\Bridge\PhpUnit\Symfony\Form\IsFormErrorsEqual;
use ParkManager\UI\Web\Form\Type\PEMCertificateType;
use Rollerworks\Component\X509Validator\Symfony\Constraint\X509CertificateBundle;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * @internal
 */
final class PEMCertificateTypeTest extends TypeTestCase
{
    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension((new ValidatorBuilder())->getValidator()),
            new HttpFoundationExtension(),
        ];
    }

    /** @test */
    public function its_accepts_empty_input(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', []);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false])->getForm();
        $form->handleRequest($request);

        self::assertTrue($form->isValid());
        self::assertNull($form->getData());
    }

    /** @test */
    public function its_accepts_certificate_without_private_key(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', [
            'certificate' => new UploadedFile(__DIR__ . '/Mocks/tls/expired-cert.pem', 'bob.rollerscapes.net.pem', null, null, true),
        ]);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false])->getForm();
        $form->handleRequest($request);

        self::assertTrue($form->isValid());

        /** @var X509CertificateBundle $data */
        $data = $form->getData();

        self::assertInstanceOf(X509CertificateBundle::class, $data);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIDKzCCAhMCCQDZHE66hI+pmjANBgkqhkiG9w0BAQUFADBUMRowGAYDVQQDDBFS\n", $data->certificate);
        self::assertNull($data->privateKey);
        self::assertCount(0, $data->caList);
    }

    /** @test */
    public function its_accepts_certificate_with_private_key(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', [
            'certificate' => new UploadedFile(__DIR__ . '/Mocks/tls/expired-cert.pem', 'bob.rollerscapes.net.pem', null, null, true),
            'privateKey' => ['file' => new UploadedFile(__DIR__ . '/Mocks/tls/private-key1.key', 'bob.rollerscapes.net.key', null, null, true)],
        ]);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false, 'requires_private_key' => true])->getForm();
        $form->handleRequest($request);

        self::assertTrue($form->isValid());

        /** @var X509CertificateBundle $data */
        $data = $form->getData();

        self::assertInstanceOf(X509CertificateBundle::class, $data);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIDKzCCAhMCCQDZHE66hI+pmjANBgkqhkiG9w0BAQUFADBUMRowGAYDVQQDDBFS\n", $data->certificate);

        self::assertNotNull($data->privateKey);
        self::assertStringStartsWith("-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDFN7758InBPIIE\n", $data->privateKey->getString());

        self::assertCount(0, $data->caList);
    }

    /** @test */
    public function its_accepts_certificate_with_password_protected_private_key(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', [
            'certificate' => new UploadedFile(__DIR__ . '/Mocks/tls/bundled.pem', 'bundled.pem', null, null, true),
            'privateKey' => [
                'file' => new UploadedFile(__DIR__ . '/Mocks/tls/private-key2.key', 'bob.rollerscapes.net.key', null, null, true),
                'passphrase' => 'BlackSilverNewfoundland',
            ],
        ]);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false, 'requires_private_key' => true])->getForm();
        $form->handleRequest($request);

        self::assertTrue($form->isValid());

        /** @var X509CertificateBundle $data */
        $data = $form->getData();

        self::assertInstanceOf(X509CertificateBundle::class, $data);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIF6DCCA9ACCQDJu4SZCeLF4jANBgkqhkiG9w0BAQ0FADCBtTELMAkGA1UEBhMC\n", $data->certificate);

        self::assertNotNull($data->privateKey);
        self::assertStringStartsWith("-----BEGIN PRIVATE KEY-----\nMIIJRAIBADANBgkqhkiG9w0BAQEFAASCCS4wggkqAgEAAoICAQDBGY4+I3567vP8\n", $data->privateKey->getString());

        self::assertCount(0, $data->caList);
    }

    /** @test */
    public function its_accepts_combined_cert_key_file(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', [
            'certificate' => new UploadedFile(__DIR__ . '/Mocks/tls/bundled.pem', 'bundled.pem', null, null, true),
            'privateKey' => ['file' => new UploadedFile(__DIR__ . '/Mocks/tls/bundled.pem', 'bundled.pem', null, null, true)],
        ]);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false, 'requires_private_key' => true])->getForm();
        $form->handleRequest($request);

        self::assertTrue($form->isValid());

        /** @var X509CertificateBundle $data */
        $data = $form->getData();

        self::assertInstanceOf(X509CertificateBundle::class, $data);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIF6DCCA9ACCQDJu4SZCeLF4jANBgkqhkiG9w0BAQ0FADCBtTELMAkGA1UEBhMC\n", $data->certificate);

        self::assertNotNull($data->privateKey);
        self::assertStringStartsWith("-----BEGIN PRIVATE KEY-----\nMIIJRAIBADANBgkqhkiG9w0BAQEFAASCCS4wggkqAgEAAoICAQDBGY4+I3567vP8\n", $data->privateKey->getString());

        self::assertCount(0, $data->caList);
    }

    /** @test */
    public function its_accepts_certificate_with_private_key_ca_list(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', [
            'certificate' => new UploadedFile(__DIR__ . '/Mocks/tls/expired-cert.pem', 'bob.rollerscapes.net.pem', null, null, true),
            'privateKey' => ['file' => new UploadedFile(__DIR__ . '/Mocks/tls/private-key1.key', 'bob.rollerscapes.net.key', null, null, true)],
            'caList' => [
                new UploadedFile(__DIR__ . '/Mocks/tls/digicert-root.pem', 'digicert-root.pem', null, null, true),
                new UploadedFile(__DIR__ . '/Mocks/tls/digicert-ca.pem', 'digicert-ca.pem', null, null, true),
            ],
        ]);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false, 'requires_private_key' => true])->getForm();
        $form->handleRequest($request);

        self::assertTrue($form->isValid());

        /** @var X509CertificateBundle $data */
        $data = $form->getData();

        self::assertInstanceOf(X509CertificateBundle::class, $data);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIDKzCCAhMCCQDZHE66hI+pmjANBgkqhkiG9w0BAQUFADBUMRowGAYDVQQDDBFS\n", $data->certificate);

        self::assertNotNull($data->privateKey);
        self::assertStringStartsWith("-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDFN7758InBPIIE\n", $data->privateKey->getString());

        self::assertArrayHasKey('digicert-root.pem', $data->caList);
        self::assertArrayHasKey('digicert-ca.pem', $data->caList);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIDrzCCApegAwIBAgIQCDvgVpBCRrGhdWrJWZHHSjANBgkqhkiG9w0BAQUFADBh", $data->caList['digicert-root.pem']);
        self::assertStringStartsWith("-----BEGIN CERTIFICATE-----\nMIIElDCCA3ygAwIBAgIQAf2j627KdciIQ4tyS8+8kTANBgkqhkiG9w0BAQsFADBh", $data->caList['digicert-ca.pem']);
    }

    /** @test */
    public function its_rejects_encrypted_private_key_without_passphrase(): void
    {
        $request = Request::create('/', 'POST');
        $request->request->set('tls_info', [
            'certificate' => new UploadedFile(__DIR__ . '/Mocks/tls/expired-cert.pem', 'bob.rollerscapes.net.pem', null, null, true),
            'privateKey' => ['file' => new UploadedFile(__DIR__ . '/Mocks/tls/private-key-with-unknown-pass.key', 'private-key.key', null, null, true)],
        ]);

        $form = $this->factory->createNamedBuilder('tls_info', PEMCertificateType::class, null, ['required' => false, 'requires_private_key' => true])->getForm();
        $form->handleRequest($request);

        self::assertFalse($form->isValid());

        $formError = new FormError('tls.invalid_key_file', null, ['{{ value }}' => 'array']);
        $formError->setOrigin($form->get('privateKey'));

        self::assertThat($form->getErrors(), new IsFormErrorsEqual([$formError]));
    }
}
