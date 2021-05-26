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
    protected static function assertSelectorTranslatedTextContains(string $selector, string $id, array $parameters = [], ?string $domain = null, string $message = ''): void
    {
        $locale = null;

        if (isset($parameters['__trans_locale'])) {
            $locale = $parameters['__trans_locale'];
            unset($parameters['__trans_locale']);
        }

        $translated = static::$container->get('translator')->trans($id, $parameters, $domain, $locale);
        $translated = trim(preg_replace('/(?:\s{2,}+|[^\S ])/', ' ', strip_tags($translated)));

        try {
            self::assertSelectorExists($selector);
            self::assertThat(self::executePrivateMethod('getCrawler'),
                new CrawlerSelectorTextContains($selector, $translated),
                $message
            );
        } catch (ExpectationFailedException $exception) {
            $response = self::executePrivateMethod('getResponse');
            \assert($response instanceof Response);

            if (($serverExceptionMessage = $response->headers->get('X-Debug-Exception'))
                && ($serverExceptionFile = $response->headers->get('X-Debug-Exception-File'))) {
                $serverExceptionFile = explode(':', $serverExceptionFile);
                $exception->__construct($exception->getMessage(), $exception->getComparisonFailure(), new ErrorException(rawurldecode($serverExceptionMessage), 0, 1, rawurldecode($serverExceptionFile[0]), $serverExceptionFile[1]), $exception->getPrevious());
            } else {
                $exception->__construct($exception->getMessage(), $exception->getComparisonFailure(), new PreconditionFailedHttpException(rawurldecode($response->getContent()), $exception->getPrevious()));
            }

            throw $exception;
        }
    }

    private static function executePrivateMethod(string $name, ?array $parameters = null)
    {
        $method = (new ReflectionClass(static::class))->getMethod($name);
        $method->setAccessible(true);

        return $method->invoke(null, $parameters);
    }
}
