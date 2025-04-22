<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Twig;

use Lifthill\Component\Common\Domain\Model\ByteSize;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Translation\ParameterValue;
use ParkManager\Infrastructure\Translation\Translator;
use ParkManager\Infrastructure\Twig\ParkManagerTextExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Translation\Loader\ArrayLoader as TranslatorArrayLoader;
use Symfony\Component\Translation\Translator as SfTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ArrayLoader as TwigArrayLoader;

/**
 * @internal
 */
final class ParkManagerTextExtensionTest extends TestCase
{
    private function createExtension(SfTranslator $realTranslator = null): ParkManagerTextExtension
    {
        $translator = new Translator($realTranslator ?? new SfTranslator('en'), new Container());

        return new ParkManagerTextExtension($translator);
    }

    private function createTwigEnvironment(): TwigEnvironment
    {
        return new TwigEnvironment(new TwigArrayLoader());
    }

    /** @test */
    public function it_translates_with_escaping_arguments(): void
    {
        $translator = new SfTranslator('en');
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('array', new TranslatorArrayLoader());

        // EN
        $translator->addResource('array', [
            'space.header' => 'Space { domain_name }',
            'space.description' => '<p>Webhosting Space provides FTP, Mailboxes, etc.</p>',
            'byte_size' => [
                'format' => '{ value, number } with { unit }',
                'byte_size.inf' => 'Unlimited',
                'unit.byte' => 'Byte',
                'unit.b' => 'Byte',
                'unit.kb' => 'KB',
                'unit.kib' => 'KiB',
                'unit.mb' => 'MB',
                'unit.mib' => 'MiB',
                'unit.gb' => 'GB',
                'unit.gib' => 'GiB',
            ],
        ], 'en', 'messages+intl-icu');
        $translator->addResource('array', [
            'space.spec' => '<b>Storage:</b> { size }<br> <b>Traffic:</b> { traffic }.',
        ], 'en', 'info+intl-icu');

        // NL
        $translator->addResource('array', [
            'space.header' => 'Space { domain_name }',
            'space.description' => '<p>Webhosting Space bied oa. FTP, Email, etc.</p>',
            'byte_size.format' => '{ value, number } met { unit }',
        ], 'nl', 'messages+intl-icu');
        $translator->addResource('array', [
            'space.spec' => '<b>Opslag:</b> { size }<br> <b>Dataverkeer:</b> { traffic }.',
        ], 'nl', 'info+intl-icu');

        $extension = $this->createExtension($translator);
        $env = $this->createTwigEnvironment();

        $traffic = new class() implements ParameterValue {
            public function format(string $locale, callable $escaper, TranslatorInterface $translator): string
            {
                if ($locale === 'en') {
                    return '223 <b>Gib</b> (' . $escaper('223 <b>Gib</b>') . ')';
                }

                return '223 <b>Giga byte</b> (' . $escaper('223 <b>Giga byte</b>') . ')';
            }
        };

        // Assertions

        // locale: 'en'
        self::assertSame(
            'Space example.com',
            $extension->trans($env, 'space.header', ['domain_name' => new DomainNamePair('example', 'com')])
        );
        self::assertSame(
            '<p>Webhosting Space provides FTP, Mailboxes, etc.</p>',
            $extension->trans($env, 'space.description')
        );
        self::assertSame(
            '<b>Storage:</b> 10 with GiB<br> <b>Traffic:</b> 223 &lt;b&gt;Gib.',
            $extension->trans($env, 'space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => '223 <b>Gib'], domain: 'info')
        );
        self::assertSame(
            '<b>Storage:</b> 10 with GiB<br> <b>Traffic:</b> 223 <b>Gib</b> (223 &lt;b&gt;Gib&lt;/b&gt;).',
            $extension->trans($env, 'space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => $traffic], domain: 'info')
        );

        // locale: 'nl'
        self::assertSame(
            'Space example.com',
            $extension->trans($env, 'space.header', ['domain_name' => new DomainNamePair('example', 'com')], locale: 'nl')
        );
        self::assertSame(
            '<p>Webhosting Space bied oa. FTP, Email, etc.</p>',
            $extension->trans($env, 'space.description', locale: 'nl')
        );
        self::assertSame(
            '<b>Opslag:</b> 10 met GiB<br> <b>Dataverkeer:</b> 223 &lt;b&gt;Gib.',
            $extension->trans($env, 'space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => '223 <b>Gib'], domain: 'info', locale: 'nl')
        );
        self::assertSame(
            '<b>Opslag:</b> 10 met GiB<br> <b>Dataverkeer:</b> 223 <b>Giga byte</b> (223 &lt;b&gt;Giga byte&lt;/b&gt;).',
            $extension->trans($env, 'space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => $traffic], domain: 'info', locale: 'nl')
        );
    }

    /** @test */
    public function it_word_wraps_text(): void
    {
        $extension = $this->createExtension();
        $env = $this->createTwigEnvironment();

        // Basic tests with ASCII text
        self::assertSame('', $extension->wordwrap($env, ''));
        self::assertSame('AAAAAAAAAAAAAAAAAAAAAAAA', $extension->wordwrap($env, 'AAAAAAAAAAAAAAAAAAAAAAAA', break: '<br>'));
        self::assertSame('AAAAA<br>AAAAAAAAAAAAAAAAAAA', $extension->wordwrap($env, 'AAAAA AAAAAAAAAAAAAAAAAAA', width: 5, break: '<br>'));
        self::assertSame('&lt;b&gt;AAA&lt;/b&gt; AA', $extension->wordwrap($env, '<b>AAA</b> AA', break: '<br>'));

        // -- With cut
        self::assertSame("AAAAA\nAAAAA\nAAAAA\nAAAAA\nAAAA", $extension->wordwrap($env, 'AAAAAAAAAAAAAAAAAAAAAAAA', width: 5, cut: true));
        self::assertSame('AAAAA<br>AAAAA<br>AAAAA<br>AAAAA<br>AAAA', $extension->wordwrap($env, 'AAAAAAAAAAAAAAAAAAAAAAAA', width: 5, break: '<br>', cut: true));

        // -- Without escaping
        self::assertSame('<b>AAA</b><br>AA', $extension->wordwrap($env, '<b>AAA</b> AA', width: 5, break: '<br>', escape: false));
        self::assertSame('<b>AA<br>A</b><br>AA', $extension->wordwrap($env, '<b>AAA</b> AA', width: 5, break: '<br>', cut: true, escape: false));

        // Unicode (without cut)
        self::assertSame('さよなら', $extension->wordwrap($env, 'さよなら', width: 2, break: '<br>'));
        self::assertSame('さよなら', $extension->wordwrap($env, new UnicodeString('さよなら'), width: 2, break: '<br>'));
        self::assertSame('नमस्ते<br>दुनिया', $extension->wordwrap($env, 'नमस्ते दुनिया', width: 2, break: '<br>'));
        self::assertSame('👁👄👁', $extension->wordwrap($env, '👁👄👁', width: 2, break: '<br>'));

        // -- No escape
        self::assertSame('👁<br>👄<br>👁', $extension->wordwrap($env, '👁 👄 👁', width: 2, break: '<br>', escape: false));
        self::assertSame('👁<br><👄><br>👁', $extension->wordwrap($env, '👁 <👄> 👁', width: 2, break: '<br>', escape: false));

        // Unicode (with cut)
        self::assertSame('さよ<br>なら', $extension->wordwrap($env, 'さよなら', width: 2, break: '<br>', cut: true));
        self::assertSame('नम<br>स्ते<br>दुनि<br>या', $extension->wordwrap($env, 'नमस्ते दुनिया', width: 2, break: '<br>', cut: true));
        self::assertSame('👁👄<br>👁', $extension->wordwrap($env, '👁👄👁', width: 2, break: '<br>', cut: true));
        self::assertSame('👁<br>👄<br>👁', $extension->wordwrap($env, '👁 👄 👁', width: 2, break: '<br>', cut: true));

        // -- No escape
        self::assertSame('さよなら', $extension->wordwrap($env, new UnicodeString('さよなら'), width: 2, break: '<br>', escape: false));
        self::assertSame('👁<br>👄<br>👁', $extension->wordwrap($env, '👁 👄 👁', width: 2, break: '<br>', cut: true, escape: false));
        self::assertSame('👁<br><👄<br>><br>👁', $extension->wordwrap($env, '👁 <👄> 👁', width: 2, break: '<br>', cut: true, escape: false));
    }
}
