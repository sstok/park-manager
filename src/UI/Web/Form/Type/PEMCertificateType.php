<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use Rollerworks\Component\X509Validator\Symfony\Constraint\X509CertificateBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Sequentially;

/**
 * A PEM X.509 file-upload for a Certificate, with (optional) private-key and CA list.
 */
final class PEMCertificateType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper($this);

        $builder->add('certificate', FileType::class, [
            'required' => $options['required'],
            'constraints' => new File(['maxSize' => '2Mi']),
            'help' => 'help.file_pem_encoded',
        ]);

        if ($options['requires_private_key']) {
            $builder->add('privateKey', PEMPrivateKeyType::class, [
                'required' => $options['required'],
                'label' => 'label.private_key_file',
            ]);
        }

        $builder->add('caList', FileType::class, [
            'help' => 'help.ca_list',
            'required' => $options['required'],
            'multiple' => true,
            'constraints' => new Sequentially([
                new Count(['max' => 3, 'maxMessage' => 'tls.violation.to_many_cas_as_provided']),
                new All([
                    new File(['maxSize' => '1Mi']),
                ]),
            ]),
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['requires_private_key'] = $options['requires_private_key'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', X509CertificateBundle::class);
        $resolver->setDefault('empty_data', null);
        $resolver->setDefault('requires_private_key', false);
        $resolver->setAllowedTypes('requires_private_key', ['boolean']);
    }

    /**
     * @param \Traversable<FormInterface> $forms
     */
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        // No-op. Fields are empty by default.
    }

    /**
     * @param \Traversable<FormInterface> $forms
     */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        /** @var FormInterface[] $fields */
        $fields = iterator_to_array($forms);

        $certificate = $fields['certificate']->getData();

        if ($certificate === null) {
            return;
        }

        $certificate = $this->extractCertData($certificate);
        $privateKey = isset($fields['privateKey']) ? $fields['privateKey']->getData() : null;
        $caList = [];

        foreach ($fields['caList']->getData() ?? [] as $file) {
            if ($file !== null) {
                $caList[$file->getClientOriginalName()] = $this->extractCertData($file);
            }
        }

        $viewData = new X509CertificateBundle($certificate, $privateKey, $caList);
    }

    private function extractCertData(UploadedFile $certificate): string
    {
        $x509Read = @openssl_x509_read($fileContents = file_get_contents($certificate->getPathname()));

        if ($x509Read === false) {
            throw new TransformationFailedException('Unable to read certificate data: : ' . openssl_error_string());
        }

        $contents = '';
        @openssl_x509_export($x509Read, $contents);
        unset($x509Read);

        // When the file contains a private-key remove it now (unless when a test).

        // @codeCoverageIgnoreStart
        if (str_contains($fileContents, '-----BEGIN PRIVATE KEY-----') && is_uploaded_file($certificate->getPathname())) {
            // Remove the temp-file and memory-contents to prevent leaking at application level.
            unlink($certificate->getPathname());
            sodium_memzero($fileContents);
        }
        // @codeCoverageIgnoreEnd

        unset($fileContents);

        return $contents;
    }
}
