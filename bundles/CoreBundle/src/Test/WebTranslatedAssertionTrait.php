<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Test;

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
        $translated = \str_replace("\n", ' ', \strip_tags($translated));

        self::assertSelectorTextContains($selector, $translated);
    }
}
