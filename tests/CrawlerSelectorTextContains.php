<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests;

use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\DomCrawler\Crawler;

final class CrawlerSelectorTextContains extends Constraint
{
    private string $selector;
    private string $expectedText;

    public function __construct(string $selector, string $expectedText)
    {
        $this->selector = $selector;
        $this->expectedText = $expectedText;
    }

    public function toString(): string
    {
        return \sprintf('has a node matching selector "%s" with content containing "%s"', $this->selector, $this->expectedText);
    }

    /**
     * @param Crawler $crawler
     */
    protected function matches($crawler): bool
    {
        $crawler = $crawler
            ->filter($this->selector)
            ->reduce(fn (Crawler $node, int $i) => \mb_strpos($node->text(null, false), $this->expectedText) === false);

        return \count($crawler) > 0;
    }

    /**
     * @param Crawler $crawler
     */
    protected function failureDescription($crawler): string
    {
        return 'the Crawler ' . $this->toString();
    }
}
