<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Translation;

use Carbon\CarbonImmutable;
use Lifthill\Component\Common\Domain\Model\ByteSize;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Translation\ParameterValue;
use ParkManager\Domain\Translation\ParameterValueService;
use ParkManager\Infrastructure\Translation\TranslationParameterFormatter;
use ParkManager\Infrastructure\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Translation\Loader\ArrayLoader as TranslatorArrayLoader;
use Symfony\Component\Translation\Translator as SfTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslatorTest extends TestCase
{
    private SfTranslator $translator;

    protected function setUp(): void
    {
        $translator = new SfTranslator('en');
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('array', new TranslatorArrayLoader());

        // EN
        $translator->addResource('array', [
            'space.header' => 'Space { domain_name }',
            'space.description' => '<p>Webhosting Space provides FTP, Mailboxes, etc.</p>',
            'date' => '{ value, date, short }',
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

        $this->translator = $translator;
    }

    /** @test */
    public function it_delegates_all_calls(): void
    {
        $translator = new Translator($this->translator, new Container());

        self::assertSame('en', $translator->getLocale());
        self::assertSame($this->translator->getCatalogue(), $translator->getCatalogue());
        self::assertSame($this->translator->getCatalogue('en'), $translator->getCatalogue('en'));
        self::assertSame($this->translator->getCatalogue('nl'), $translator->getCatalogue('nl'));
        self::assertSame($this->translator->getCatalogue('de'), $translator->getCatalogue('de'));
        self::assertSame($this->translator->getCatalogues(), $translator->getCatalogues());

        $translator->setLocale('nl');

        self::assertSame('nl', $translator->getLocale());
    }

    /** @test */
    public function it_translates(): void
    {
        $translator = new Translator($this->translator, new Container());

        $escaper = static fn (mixed $value) => "<{$value}>";

        self::assertSame('', $translator->trans(''));
        self::assertSame('', $translator->trans(null));

        // locale: 'en'
        self::assertSame(
            'Space example.com',
            $translator->trans('space.header', ['domain_name' => 'example.com'])
        );
        self::assertSame(
            '<p>Webhosting Space provides FTP, Mailboxes, etc.</p>',
            $translator->trans('space.description')
        );

        // -- With escaper
        self::assertSame(
            'Space <example.com>',
            $translator->trans('space.header', ['domain_name' => 'example.com'], escaper: $escaper)
        );
        self::assertSame(
            '<p>Webhosting Space provides FTP, Mailboxes, etc.</p>',
            $translator->trans('space.description', escaper: $escaper)
        );

        // locale: 'nl'
        self::assertSame(
            'Space example.com',
            $translator->trans('space.header', ['domain_name' => 'example.com'], locale: 'nl')
        );
        self::assertSame(
            '<p>Webhosting Space bied oa. FTP, Email, etc.</p>',
            $translator->trans('space.description', locale: 'nl')
        );

        // -- With escaper
        self::assertSame(
            'Space <example.com>',
            $translator->trans('space.header', ['domain_name' => 'example.com'], locale: 'nl', escaper: $escaper)
        );
        self::assertSame(
            '<p>Webhosting Space bied oa. FTP, Email, etc.</p>',
            $translator->trans('space.description', locale: 'nl', escaper: $escaper)
        );

        // Ensure the global locale is used (not implicit).
        $translator->setLocale('en');
        $translator->setLocale('nl');

        self::assertSame(
            'Space <example.com>',
            $translator->trans('space.header', ['domain_name' => 'example.com'], escaper: $escaper)
        );
        self::assertSame(
            '<p>Webhosting Space bied oa. FTP, Email, etc.</p>',
            $translator->trans('space.description', escaper: $escaper)
        );
    }

    /** @test */
    public function it_translates_objects(): void
    {
        $formatterServices = new Container();
        $formatterServices->set(ParameterValueServiceMock::class, new TranslationParameterFormatterMock());
        $translator = new Translator($this->translator, $formatterServices);
        $escaper = static fn (mixed $value) => "<{$value}>";

        $traffic = new class() implements ParameterValue {
            public function format(string $locale, callable $escaper, TranslatorInterface $translator): string
            {
                if ($locale === 'en') {
                    return '223 <b>Gib</b> (' . $escaper('223 <b>Gib</b>') . ')';
                }

                return '223 <b>Giga byte</b> (' . $escaper('223 <b>Giga byte</b>') . ')';
            }
        };

        self::assertSame('7/21/21', $translator->trans('date', ['value' => new CarbonImmutable('2021-07-21T16:23:00 UTC')]));

        // locale: 'en'
        self::assertSame(
            'Space example.com',
            $translator->trans('space.header', ['domain_name' => new DomainNamePair('example', 'com')])
        );
        self::assertSame(
            '<b>Storage:</b> 10 with GiB<br> <b>Traffic:</b> 223 <b>Gib.',
            $translator->trans('space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => '223 <b>Gib'], domain: 'info')
        );
        self::assertSame(
            '<b>Storage:</b> <10 with GiB><br> <b>Traffic:</b> 223 <b>Gib</b> (<223 <b>Gib</b>>).',
            $translator->trans('space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => $traffic], domain: 'info', escaper: $escaper)
        );
        self::assertSame(
            'Space example.com ("example.com") with en',
            $translator->trans('space.header', ['domain_name' => new ParameterValueServiceMock('example.com')])
        );
        self::assertSame(
            'Space example.com ("<example.com>") with en',
            $translator->trans('space.header', ['domain_name' => new ParameterValueServiceMock('example.com')], escaper: $escaper)
        );

        // locale: 'nl'
        self::assertSame(
            'Space example.com',
            $translator->trans('space.header', ['domain_name' => new DomainNamePair('example', 'com')], locale: 'nl')
        );
        self::assertSame(
            '<b>Opslag:</b> 10 met GiB<br> <b>Dataverkeer:</b> 223 <b>Gib.',
            $translator->trans('space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => '223 <b>Gib'], domain: 'info', locale: 'nl')
        );
        self::assertSame(
            '<b>Opslag:</b> <10 met GiB><br> <b>Dataverkeer:</b> 223 <b>Giga byte</b> (<223 <b>Giga byte</b>>).',
            $translator->trans('space.spec', ['size' => new ByteSize(10, 'gib'), 'traffic' => $traffic], domain: 'info', locale: 'nl', escaper: $escaper)
        );
        self::assertSame(
            'Space example.com ("example.com") with nl',
            $translator->trans('space.header', ['domain_name' => new ParameterValueServiceMock('example.com')], locale: 'nl')
        );
        self::assertSame(
            'Space example.com ("<example.com>") with nl',
            $translator->trans('space.header', ['domain_name' => new ParameterValueServiceMock('example.com')], locale: 'nl', escaper: $escaper)
        );
    }
}

/**
 * @internal
 */
final class ParameterValueServiceMock implements ParameterValueService
{
    public function __construct(
        public mixed $value,
    ) {}
}

class TranslationParameterFormatterMock implements TranslationParameterFormatter
{
    public function format(
        ParameterValueService $value,
        string $locale,
        callable $escaper,
        TranslatorInterface $translator
    ): string {
        \assert($value instanceof ParameterValueServiceMock);

        return sprintf('%s ("%s") with %s', $value->value, $escaper($value->value), $locale);
    }
}
