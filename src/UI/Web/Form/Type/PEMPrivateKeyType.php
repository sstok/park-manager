<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use ParagonIE\HiddenString\HiddenString;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * A PEM X.509 file-upload for private-key (with optional passphrase).
 */
final class PEMPrivateKeyType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper($this);

        $builder->add('passphrase', PasswordType::class, [
            'required' => false,
            'label' => 'label.private_key_passphrase',
            'help' => 'help.private_key_passphrase',
        ]);
        $builder->add('file', FileType::class, [
            'required' => $options['required'],
            'constraints' => new File(['maxSize' => '1Mi']),
            'help' => 'help.file_pem_encoded',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('empty_data', null);
        $resolver->setDefault('data_class', HiddenString::class);
        $resolver->setDefault('invalid_message', 'tls.invalid_key_file');
    }

    public function mapDataToForms($viewData, iterable $forms): void
    {
        // No-op. Fields are empty by default.
    }

    /**
     * @param \RecursiveIteratorIterator $forms
     */
    public function mapFormsToData(iterable $forms, &$viewData): void
    {
        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        /** @var UploadedFile|null $file */
        $file = $forms['file']->getData();

        // Let the validator handle this case. No more than 1 MiB
        if ($file === null || ! $file->isValid() || ! $file->isReadable() || $file->getSize() > (1000 * 1000)) {
            return;
        }

        $passphrase = (string) $forms['passphrase']->getData();
        $viewData = $this->extractPrivateKey($file, $passphrase);
    }

    private function extractPrivateKey(?UploadedFile $file, string $passphrase): HiddenString
    {
        // Notice. In case of an exception the file is properly invalid. So no need to zero the memory or remove the file.
        // Actual compatibility and quality is validated later.
        $privateR = @\openssl_pkey_get_private($fileContents = \file_get_contents($file->getPathname()), $passphrase);

        if ($privateR === false) {
            throw new TransformationFailedException(
                'Unable to read private key-data, invalid key provided or missing passphrase: ' . \openssl_error_string()
            );
        }

        $contents = '';
        @\openssl_pkey_export($privateR, $contents);
        @\openssl_pkey_free($privateR);

        $viewData = new HiddenString($contents);
        \sodium_memzero($contents);

        // Only perform this when the file is actually and upload (and not a test).
        // If the file also contains a cert leave it. We will remove it later.
        //
        // @codeCoverageIgnoreStart
        if (\mb_strpos($fileContents, '-----BEGIN CERTIFICATE-----') === false && \is_uploaded_file($file->getPathname())) {
            // Remove the temp-file to prevent leaking at application level.
            // Nullifying the file contents unneeded as the file will be written to
            // disk in a later state (but under a different ownership).
            \unlink($file->getPathname());
        }
        // @codeCoverageIgnoreEnd

        unset($contents, $fileContents);

        return $viewData;
    }
}
