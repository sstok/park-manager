<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\DomainName;

use ParkManager\Domain\DomainName\DomainNamePair;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DomainNamePairTest extends TestCase
{
    /** @test */
    public function equatable(): void
    {
        $namePair = new DomainNamePair('example', 'com');

        self::assertTrue($namePair->equals($namePair));
        self::assertTrue($namePair->equals(clone $namePair));

        self::assertFalse($namePair->equals(new DomainNamePair('example', 'net')));
        self::assertFalse($namePair->equals(new DomainNamePair('park-manager', 'com')));
    }

    /** @test */
    public function it_provides_truncated_string_version(): void
    {
        self::assertSame('example.com', (new DomainNamePair('example', 'com'))->toTruncatedString());
        self::assertSame('日本レジストリサービス.jp', (new DomainNamePair('日本レジストリサービス', 'jp'))->toTruncatedString());
        self::assertSame('例子.测试', (new DomainNamePair('例子', '测试'))->toTruncatedString());
        self::assertSame('绝不会放弃你永远不会让你失望永远不会跑[...].测试', (new DomainNamePair('绝不会放弃你永远不会让你失望永远不会跑来跑去和抛弃你永远不会让你哭泣永远不会说再见永远不会撒谎和伤害你', '测试'))->toTruncatedString(27));
        self::assertSame('aaaaaaaaaaaaaaaaaa[...].com', (new DomainNamePair('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'com'))->toTruncatedString(27));
        self::assertSame('aaaaaaaaaaaaaaaaaaa[...].com', (new DomainNamePair('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'com'))->toTruncatedString(28));
        self::assertSame('aaaaaaaaaaaaaaaa[...].co.uk', (new DomainNamePair('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'co.uk'))->toTruncatedString(27));
        self::assertSame('aaaaaaaaaaaaaaaa[...].co.uk', (new DomainNamePair('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbbbbbbbbbbb', 'co.uk'))->toTruncatedString(27));
        self::assertSame('aaaaaaaaaaaaaaaa[...].co.uk', (new DomainNamePair('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbbbbbbbbbbb', 'co.uk'))->toTruncatedString(27));
        self::assertSame('yidianliangdiansan[...].com', (new DomainNamePair('yidianliangdiansandiansidianwudianliudianqidianbadianjiudianshi', 'com'))->toTruncatedString(27));
    }
}
