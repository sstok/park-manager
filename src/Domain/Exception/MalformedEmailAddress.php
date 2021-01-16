<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

use InvalidArgumentException;

final class MalformedEmailAddress extends InvalidArgumentException
{
    public static function missingAtSign(string $address): self
    {
        return new self(\sprintf('Malformed email address "%s" (missing @)', $address), 1);
    }

    public static function idnError(string $address, int $errors): self
    {
        /**
         * IDNA errors.
         *
         * @see http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
         * @see \Pdp\IDNAConverterTrait
         */
        static $idnErrors = [
            \IDNA_ERROR_EMPTY_LABEL => 'a non-final domain name label (or the whole domain name) is empty',
            \IDNA_ERROR_LABEL_TOO_LONG => 'a domain name label is longer than 63 bytes',
            \IDNA_ERROR_DOMAIN_NAME_TOO_LONG => 'a domain name is longer than 255 bytes in its storage form',
            \IDNA_ERROR_LEADING_HYPHEN => 'a label starts with a hyphen-minus ("-")',
            \IDNA_ERROR_TRAILING_HYPHEN => 'a label ends with a hyphen-minus ("-")',
            \IDNA_ERROR_HYPHEN_3_4 => 'a label contains hyphen-minus ("-") in the third and fourth positions',
            \IDNA_ERROR_LEADING_COMBINING_MARK => 'a label starts with a combining mark',
            \IDNA_ERROR_DISALLOWED => 'a label or domain name contains disallowed characters',
            \IDNA_ERROR_PUNYCODE => 'a label starts with "xn--" but does not contain valid Punycode',
            \IDNA_ERROR_LABEL_HAS_DOT => 'a label contains a dot=full stop',
            \IDNA_ERROR_INVALID_ACE_LABEL => 'An ACE label does not contain a valid label string',
            \IDNA_ERROR_BIDI => 'a label does not meet the IDNA BiDi requirements (for right-to-left characters)',
            \IDNA_ERROR_CONTEXTJ => 'a label does not meet the IDNA CONTEXTJ requirements',
        ];

        $res = [];

        foreach ($idnErrors as $error => $reason) {
            if ($errors & $error) {
                $res[] = $reason;
            }
        }

        $errorsString = $res === [] ? 'Unknown IDNA conversion error.' : \implode(', ', $res) . '.';

        return new self(\sprintf('Malformed email address "%s" (IDN Error reported %s)', $address, $errorsString), 2);
    }

    public static function patternMultipleWildcards(string $address): self
    {
        return new self(\sprintf('Malformed email address pattern "%s", multiple wildcards found.', $address), 3);
    }

    public static function patternWildcardInLabel(string $address): self
    {
        return new self(\sprintf('Malformed email address pattern "%s", wildcard found in label part.', $address), 4);
    }
}
