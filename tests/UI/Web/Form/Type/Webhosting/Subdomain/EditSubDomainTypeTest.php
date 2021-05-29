<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Webhosting\Subdomain;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Command\Webhosting\SubDomain\EditSubDomain;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;
use ParkManager\Domain\Webhosting\SubDomain\TLS\Certificate;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use ParkManager\Tests\UI\Web\Form\MessageFormTestCase;
use ParkManager\UI\Web\Form\Type\Webhosting\Subdomain\EditSubDomainType;
use ParkManager\UI\Web\Form\Type\Webhosting\WebhostingDomainNameSelector;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * @internal
 */
final class EditSubDomainTypeTest extends MessageFormTestCase
{
    public const SUB_DOMAIN_ID_1 = '497ec19e-1cb1-415c-9692-4ecc745fe4c8';
    public const DOMAIN_NAME_ID_1 = 'afd26739-1104-4eb5-a1a4-4b9995234fd2';

    private DomainName $domainName;

    protected static function getCommandName(): string
    {
        return EditSubDomain::class;
    }

    /**
     * @return FormTypeInterface[]
     */
    protected function getTypes(): array
    {
        return [
            $this->getMessageType(),
            new WebhostingDomainNameSelector(new DomainNameRepositoryMock([$this->createDomainName()])),

        ];
    }

    private function createDomainName(): DomainName
    {
        $space = SpaceRepositoryMock::createSpace(SpaceRepositoryMock::ID1);

        return $this->domainName = DomainName::registerForSpace(DomainNameId::fromString(
            self::DOMAIN_NAME_ID_1
        ), $space, new DomainNamePair('example', 'com'));
    }

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
    public function it_contains_no_option_to_remove_tls_when_not_in_model(): void
    {
        $model = new SubDomain(SubDomainNameId::fromString(self::SUB_DOMAIN_ID_1), $this->domainName, 'blog', '/', []);

        $form = $this->factory->create(EditSubDomainType::class, $model, ['space_id' => $model->space->id]);

        self::assertTrue($form->has('tlsInfo'));
        self::assertFalse($form->has('removeTLS'));
    }

    /** @test */
    public function it_contains_option_to_remove_tls_when_present_in_model(): void
    {
        $model = new SubDomain(SubDomainNameId::fromString(self::SUB_DOMAIN_ID_1), $this->domainName, 'blog', '/', []);
        $model->assignTlsConfiguration($this->createMock(Certificate::class));

        $form = $this->factory->create(EditSubDomainType::class, $model, ['space_id' => $model->space->id]);

        self::assertTrue($form->has('tlsInfo'));
        self::assertTrue($form->has('removeTLS'));
    }

    /** @test */
    public function it_handles_command_without_tls_and_previously_set(): void
    {
        $this->commandHandler = static fn () => '';

        $model = new SubDomain($id = SubDomainNameId::fromString(self::SUB_DOMAIN_ID_1), $this->domainName, 'blog', '/', []);
        $model->assignTlsConfiguration($this->createMock(Certificate::class));

        $request = Request::create('/', 'POST', ['edit_sub_domain' => [
            'root_domain' => self::DOMAIN_NAME_ID_1,
            'name' => 'news',
            'homeDir' => 'news/',
        ]]);

        $form = $this->factory->create(EditSubDomainType::class, $model, ['space_id' => $model->space->id]);
        $form->handleRequest($request);

        self::assertFormIsValid($form);
        self::assertEquals(
            new EditSubDomain($id, DomainNameId::fromString(self::DOMAIN_NAME_ID_1), 'news', 'news/'),
            $this->dispatchedCommand
        );
    }

    /** @test */
    public function it_handles_command_with_new_tls_information(): void
    {
        $this->commandHandler = static fn () => '';

        $model = new SubDomain($id = SubDomainNameId::fromString(self::SUB_DOMAIN_ID_1), $this->domainName, 'blog', '/', []);
        $model->assignTlsConfiguration($this->createMock(Certificate::class));

        $request = Request::create('/', 'POST', ['edit_sub_domain' => [
            'root_domain' => self::DOMAIN_NAME_ID_1,
            'name' => 'news',
            'homeDir' => 'news/',
            'tlsInfo' => [
                'certificate' => new UploadedFile(__DIR__ . '/../../Mocks/tls/bundled.pem', 'bundled.pem', null, null, true),
                'privateKey' => [
                    'passphrase' => 'BlackSilverNewfoundland',
                    'file' => new UploadedFile(__DIR__ . '/../../Mocks/tls/private-key2.key', 'bob.rollerscapes.net.key', null, null, true),
                ],
            ],
        ]]);

        $form = $this->factory->create(EditSubDomainType::class, $model, ['space_id' => $model->space->id]);
        $form->handleRequest($request);

        // Notice. the empty-line in cert and private must be kept!
        self::assertFormIsValid($form);
        self::assertEquals(
            (new EditSubDomain($id, DomainNameId::fromString(self::DOMAIN_NAME_ID_1), 'news', 'news/'))
                ->andTLSInformation(
                    <<<'CERT'
                        -----BEGIN CERTIFICATE-----
                        MIIF6DCCA9ACCQDJu4SZCeLF4jANBgkqhkiG9w0BAQ0FADCBtTELMAkGA1UEBhMC
                        TkwxEjAQBgNVBAgMCVNvbWV3aGVyZTEdMBsGA1UEBwwUTkVWRVIgRVZFUiBVU0Ug
                        RFVUQ0gxGjAYBgNVBAoMEUdsb2JleCBPbWVnYSBDb3JwMRowGAYDVQQLDBF0ZXN0
                        LmV4YW1wbGUub2NkYzEaMBgGA1UEAwwRdGVzdC5leGFtcGxlLm9jZGMxHzAdBgkq
                        hkiG9w0BCQEWEGluZm9Abm90aGluZy5jb20wHhcNMjAxMTIyMTAzNTIzWhcNMzAx
                        MTIwMTAzNTIzWjCBtTELMAkGA1UEBhMCTkwxEjAQBgNVBAgMCVNvbWV3aGVyZTEd
                        MBsGA1UEBwwUTkVWRVIgRVZFUiBVU0UgRFVUQ0gxGjAYBgNVBAoMEUdsb2JleCBP
                        bWVnYSBDb3JwMRowGAYDVQQLDBF0ZXN0LmV4YW1wbGUub2NkYzEaMBgGA1UEAwwR
                        dGVzdC5leGFtcGxlLm9jZGMxHzAdBgkqhkiG9w0BCQEWEGluZm9Abm90aGluZy5j
                        b20wggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQDBGY4+I3567vP85jHR
                        OyllFiTy4Y3a9Z0rmhG78Zoe+vAMi39QeUtCClJEjz+r+sEPtwaoK1+KpzUeKjZz
                        8D4ZmA4XSPWXOVX1O1j5mkWcdNDCgOyhZWUT+3YVqWxQgoNgnLcAefqP4KrOohhK
                        eFaIi6Vt6qOnWEN+JahxxQBzR5p0a5aB9SE5v9Rlushr6lkeg6+Ri89SmNsTO8bb
                        dqdjX/yobk0oEYREtZUiHj05/iBIaEPm3QD3GQ+ZT1bcXrzzfQoFBgvzQ7/zSbYX
                        6MuqNzM0KhVstNuDLamB7XpGcY2ajFdZ95wZCV2//JeWzaE7EDMsGYBix1rZwzae
                        RCfXl3O4H1rlRN7OUF3Li2G4gOAmmCfZ0+Ty714Yy7seHN7tAYCQRdRDxIUQ4KeB
                        ULGT6fNDyE9C57Ij427hKvG5AL58ZYgwV/nzz2kAhTfYqRedqQcZxgBuvvt1bkyW
                        gZRhi+TeROilt+FuAztgiaDneLCPE901xyrSpSXfNTZ4AfcJMhXxU/3EnKJa/JRL
                        Zf0A9bYUJ207bXzYQvd4kILQ8NTSp2X0mGPY4xsmYlNww7+JoS5eeDSGakh8gSus
                        ishJ/ktmO9IWmEP3K+ifokeeBD/n4lsV6QtIFOHee6/WQcp3ugGOL2MNgV7n+/CP
                        ChsjY3KHFdE4UUHHprijKgPH2QIDAQABMA0GCSqGSIb3DQEBDQUAA4ICAQBdGMXh
                        zpb+hU+3QUvCWMGZ/D6oQxAIMKi94FPjH26qPgPYlL+wvVkHggPBNN5/06I67s+y
                        iXrW8BwQDSRFW1Ixmt2On0xhREsOAm79sM/A7F4NCRMpWOG8OIuQNFrmj5h2NIOc
                        udpMRfBarJXctHNMznC9gFXsSyaqk+EDw7BHsLRmVQnrUoG4ssznBrO63Uyqad0q
                        FC4dV/glEDLGjNfaOV3ZBfC1j+eBC1kqH8lwjmKexgvwAulRlIKxruzIcHhaUq3Z
                        uyeD0sDRDaA7ySyYVUY1JhkbIUwV/6S7upjhIWvGwkmICdTzoBWRem75WwYzxTox
                        iLMT9Gz7dO9ZuvqtUBdkB29znbymeViwzEgUylH6ngLLGGn4vuF8zTfPoFvMKWEB
                        7pDurApq5x8ypyUjzZSNEZQMTE6keCuCmhROx5sbqTKv6AI4e2soTQ7iqVXhLP9h
                        Nq0si+7Fup8djrAgXTAN0373of5tqaIQv57WjtAkQPXZgwz0rDQgPzq2EQh5BGV+
                        ef4P/09bSEfw0Uj0iioG/YqRA//5//sdGMgzTeL+1X4GYnMHTR6u7ZgaqbkhWDWe
                        HqCMmriRjUxhKRCDwbYDfmaKp6AJDxV7w6F+6mr8fYR9tf1pV0Bqgm6B8/2+s9O1
                        PHwSGU2XcQeXwDZlg8nlS981flenf4tMw1GbSQ==
                        -----END CERTIFICATE-----

                        CERT,
                    new HiddenString(
                        <<<'PRIVATE_KEY'
                            -----BEGIN PRIVATE KEY-----
                            MIIJRAIBADANBgkqhkiG9w0BAQEFAASCCS4wggkqAgEAAoICAQDBGY4+I3567vP8
                            5jHROyllFiTy4Y3a9Z0rmhG78Zoe+vAMi39QeUtCClJEjz+r+sEPtwaoK1+KpzUe
                            KjZz8D4ZmA4XSPWXOVX1O1j5mkWcdNDCgOyhZWUT+3YVqWxQgoNgnLcAefqP4KrO
                            ohhKeFaIi6Vt6qOnWEN+JahxxQBzR5p0a5aB9SE5v9Rlushr6lkeg6+Ri89SmNsT
                            O8bbdqdjX/yobk0oEYREtZUiHj05/iBIaEPm3QD3GQ+ZT1bcXrzzfQoFBgvzQ7/z
                            SbYX6MuqNzM0KhVstNuDLamB7XpGcY2ajFdZ95wZCV2//JeWzaE7EDMsGYBix1rZ
                            wzaeRCfXl3O4H1rlRN7OUF3Li2G4gOAmmCfZ0+Ty714Yy7seHN7tAYCQRdRDxIUQ
                            4KeBULGT6fNDyE9C57Ij427hKvG5AL58ZYgwV/nzz2kAhTfYqRedqQcZxgBuvvt1
                            bkyWgZRhi+TeROilt+FuAztgiaDneLCPE901xyrSpSXfNTZ4AfcJMhXxU/3EnKJa
                            /JRLZf0A9bYUJ207bXzYQvd4kILQ8NTSp2X0mGPY4xsmYlNww7+JoS5eeDSGakh8
                            gSusishJ/ktmO9IWmEP3K+ifokeeBD/n4lsV6QtIFOHee6/WQcp3ugGOL2MNgV7n
                            +/CPChsjY3KHFdE4UUHHprijKgPH2QIDAQABAoICABjvguXVUYzwdINw+nfpauQJ
                            4wWWSOpAk2ZBBA9AGMXtY7hK/0rWDvjdOlhuIyvDOtEbsnle+HyAMSTPEK8SFALc
                            Ft285zH7DnWXj1rUKC8XCqpDWctRu6bD7zDG6xzObca5Fgyys7+GpKgAWKCtP4ds
                            eRVjmnSOulB87m6aoP9B/NkR16K6k+rQMc5dO2psHcwJ908VPdWNtaSbMIfAn8b+
                            azMY40/MS0dQJ4z7WK63eVXAWRxj568BBnmmvDHC7iWHPRb+++YTFqOFHkWiI0K8
                            1BjlYlTiWgJrkBseHo8gNMgDXXhZuEenZPrY1H592Vc2NVV5iZG36qx6QABaDsbX
                            iv6tgRDwhyJsYsmN4TPb7FHfpAiOg4u852TBB+ARHhX31fkfvt8qR/DpMIp8A7ef
                            OcXlV2Jg/yyQ0VTfAjV5IvwktGlX0tocxw65/HKVkJHVSuLdskUbaYnUllgYDG4A
                            KdLYtYSZdTlLZA2QnbNcjSs22I9O/Y/r4uzyxwp1QvMwEL1Uqhi3YcHA53lweJf1
                            DDLfh+Tt9p0LfbY7Qi4kGJbxK1Zu6NJFFSqpiGk3EGV4SKXVC4OfQA8AXiRmyg+v
                            8fYr9g63XFzCQ9wFYhPEevfqjYlzRq+5+BC6kAYBYnb2J58tvyPsN9kEKFcPt3u+
                            q6sHK9Z/abiKx+o5r1ENAoIBAQD6pLsjml7npf3Rgz3/GVBJ4bawn1EAR/KHrUH4
                            vwxOKSw5AJWV7NsHmHVIULVlOAwC82xRoF7Oi2k1HOPc/rMXlsMrU+pH3Eg8ZcHv
                            CEZjA0IZOroBL3PHQH9V/LcEdk3aDVlJLmcLHrAMtVEoLnd7MtJKLlwUpB0jpTIx
                            bUzKU+/m2fjSTsPsKsLo67Zh9Bc4r2dqvvnyg6dZeCKI3nC23M6mM0ksS67kRj5W
                            um1eNAX0iwAERkhlpLCRKYNH5knNo35sdtpe+uNIAhqW8rcSTXrIy/OpF3hU/v+q
                            HyLjb0DJ0EnYh9oQMAww111GJ3z7PY/0clnl3hA/Or1IubYPAoIBAQDFOgDuVD/G
                            RoyR70aIW5fSzX/3z27Tbb6ZYu1gl8pEngE3dMnIULWwCOpUgaOzoN2w7pDMOEL6
                            lS7HMDXcRRrscEDA8rjSXwK72Bf8ucskYIstpdToJLdme9qYM2sEWKsa17NeobvJ
                            aYOX7GzUsFvGqdFbXt3TK0xYfTt/gYBE7zkGY5XZ8C2vk1vcU1v/WF1z8GImf/Xc
                            gtNBzXK8wzfV0ksoizit2UUxRjpknoa5jyDMjDHIrcLCX4vqxV31KIcaEfyAu0Gg
                            MPciaDha2qj6RgX20bZi6p2bSnQE/x+t1llfnQFD8NbWjjKEm1ICbi0Sh0Cr2ySe
                            3gQKdhfsrUuXAoIBAQC8lIbM8c/oIKcJJOLXdZdID1BiMWxDdt6OoJgbbLr4b9f7
                            B2qExBHD9DyB7V+dyX6YFchw3eWXJ6M/t/3lf6kF3YP4rImACcg70SAKFcLHqmLo
                            EpxyWd6Vkvx7mMmmzNAkeJiuDACxiCRTW3S1c8in5AP+lkTnrtbRw/Aw6hi1vlOo
                            8GVa7rJQlZfSoc62gm2aU7bOxOPQSKf4FAB/1EUgAKBmwf2TG1p6HDO5E0lIcIHu
                            jUq07KB6AWBrx70jsqvi0dlECGSiw8ePnS2eVv3RAoSYYYNxvNvnr3hy6jxYvJMV
                            hOaayRQTy+LQOKnQJ9PMQHR4KjVjUyIMQMi72CaXAoIBAQCcWyOpzfjRQ9TlTQBp
                            0dNHN8uftj9yNqrIB3mQ/kVIqMrmIBxoRjQD3s2HOU+CdKHMRxVcEa5n4iU3nKzW
                            d6Kkl0l+re6AOPp92Q8LOHAn4rHz+mgTsigDg2UFDJ5mz7S2jxKQjz/EqXW9151f
                            8ICRusdS3J6XbtgTvxSQPSZngA+BVSnToWlWrEhDH4LrqC5OX+AwDXno236HyEyS
                            AWIejZ0wA09n47vLGJXqdxLvwNyLzQkaw0aHuh++e8HFPd/9dwzrMYkRakBCcsVa
                            occcwq6vwgoJ2V4hYqEf5PJEao5oEpySNDjd19WM82XMr3PkIH3QwOcDW5dwg7br
                            12b5AoIBAQCjXXffG7XVXD3gUANorcDmuOd61MrvzCJ71nE10DBlJTwStpn/noN5
                            ZnFXWGJlx258+9iDloD1W4LF+6p/D81UM5APQWXIm5nUBxJYOTtpU3nNuXnXlJ5z
                            41eGIMJoM5b108V1njuzWaTiumwEn5KPApLDLbGHAHAUij689QReCfplUIjA5cv/
                            E12c9FxIuPnyS/hySBAydEOYQLPkoshkAXrO0wk/ipKumfNg+epY3daozkae8AvS
                            7PzTiBL09El/Q7FHzzO+GCTqdIbNwRevqxw4fFJJI/ZO12t6Devij/B+2yxyAwkL
                            tM50hqJnx2rLVXJY0x2q2jxxiQCn2NOg
                            -----END PRIVATE KEY-----

                            PRIVATE_KEY
                    )
                ),
            $this->dispatchedCommand
        );
    }

    /** @test */
    public function it_handles_command_removing_tls(): void
    {
        $this->commandHandler = static fn () => '';

        $model = new SubDomain($id = SubDomainNameId::fromString(self::SUB_DOMAIN_ID_1), $this->domainName, 'blog', '/', []);
        $model->assignTlsConfiguration($this->createMock(Certificate::class));

        $request = Request::create('/', 'POST', ['edit_sub_domain' => [
            'root_domain' => self::DOMAIN_NAME_ID_1,
            'name' => 'news',
            'homeDir' => 'news/',
            'removeTLS' => '1',
        ]]);

        $form = $this->factory->create(EditSubDomainType::class, $model, ['space_id' => $model->space->id]);
        $form->handleRequest($request);

        self::assertFormIsValid($form);
        self::assertEquals(
            (new EditSubDomain($id, DomainNameId::fromString(self::DOMAIN_NAME_ID_1), 'news', 'news/'))->removeTLSInformation(),
            $this->dispatchedCommand
        );
    }
}
