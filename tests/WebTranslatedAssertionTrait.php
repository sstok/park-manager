<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests;

use ErrorException;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

trait WebTranslatedAssertionTrait
{
    /**
     * @param array<string, mixed> $parameters
     */
    protected static function assertSelectorTranslatedTextContains(string $selector, string $id, array $parameters = [], ?string $domain = null, string $message = ''): void
    {
        $locale = null;

        if (isset($parameters['__trans_locale'])) {
            $locale = $parameters['__trans_locale'];
            unset($parameters['__trans_locale']);
        }

        $translated = static::getContainer()->get('translator')->trans($id, $parameters, $domain, $locale);
        $translated = trim(preg_replace('/(?:\s{2,}+|[^\S ])/', ' ', strip_tags($translated)));

        try {
            self::assertSelectorExists($selector);
            self::assertThat(
                self::executePrivateMethod('getCrawler'),
                new CrawlerSelectorTextContains($selector, $translated),
                $message
            );
        } catch (ExpectationFailedException $exception) {
            $response = self::executePrivateMethod('getResponse');
            \assert($response instanceof Response);

            $serverExceptionMessage = $response->headers->get('X-Debug-Exception');
            $serverExceptionFile = $response->headers->get('X-Debug-Exception-File');

            if ($serverExceptionMessage !== null && $serverExceptionFile !== null) {
                $serverExceptionFile = explode(':', $serverExceptionFile);

                throw new ExpectationFailedException(
                    $exception->getMessage(),
                    $exception->getComparisonFailure(),
                    new ErrorException(
                        rawurldecode($serverExceptionMessage),
                        0,
                        1,
                        rawurldecode($serverExceptionFile[0]),
                        (int) $serverExceptionFile[1],
                        $exception->getPrevious()
                    )
                );
            }

            throw new ExpectationFailedException(
                $exception->getMessage(),
                $exception->getComparisonFailure(),
                new PreconditionFailedHttpException(rawurldecode($response->getContent()), $exception->getPrevious())
            );
        }
    }

    /**
     * @param array<int, mixed>|null $parameters
     */
    private static function executePrivateMethod(string $name, ?array $parameters = null): mixed
    {
        $method = (new ReflectionClass(static::class))->getMethod($name);
        $method->setAccessible(true);

        return $method->invoke(null, $parameters);
    }
}
