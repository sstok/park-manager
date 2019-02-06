PHP Coding Standards
====================

When contributing code to Park-Manager, you must follow its coding standards.

.. note::

    The coding standards described here only apply to the server-side, namely
    the PHP code.

    The Front-end follows a different set of standards, which you can find in
    :doc:`/contributing/code/front-end-standards.rst`

The Park-Manager Coding Standard is a set of rules for `PHP_CodeSniffer <https://github.com/squizlabs/PHP_CodeSniffer>`_.
It is based on `PSR-1 <https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md>`_
and `PSR-2 <https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md>`_,
with some noticeable exceptions/differences/extensions.

Introduction
------------

- Keep the nesting of control structures per method as small as possible;
- Use strict `object calisthenics <https://www.slideshare.net/rdohms/writing-code-you-wont-hate-tomorrow-phpce18>`_ when possible;
- Use parentheses when creating new instances that do not require arguments ``$foo = new Foo()``;
- Use Null Coalesce Operator ``$foo = $bar ?? $baz``;
- Prefer early exit over nesting conditions or using ``else``;
- Abstract exception class names and exception interface names should be suffixed with ``Exception``;
- Interfaces must not be suffixed with ``Interface``;
- Concrete exception class names should not be suffixed with ``Exception``;
- Align equals (``=``) signs in assignments;
- Align chained method calls over multiple lines;
- Add spaces around a concatenation operator ``$foo = 'Hello ' . 'World!';``;
- Add spaces between assignment, control and return statements;
- Add spaces after a negation operator ``if (! $cond)``;
- Add spaces after a type cast ``$foo = (int) '12345';``;
- Use apostrophes for enclosing strings (``'``);
- Use camelCase, not underscores, for variable, function and method names and arguments;
- Use underscores for option names and parameter names;
- Always use strict comparisons;
- Always add ``declare(strict_types=1)`` at the beginning of a file;
- Always add native types where possible;
- Always chop down method calls that exceed 120 characters (don't wrap);
- Omit phpDoc for parameters/returns with native types, unless adding description;
- Don't use ``@author``, ``@since`` and similar annotations that duplicate Git information;
- Don't wrap definitions (class/interface/trait/function and closures);
- Assignment in condition is not allowed.

.. tip::

    You can check your code for Park-Manager coding standard by running the following command:

    .. code-block:: bash

        $ vendor/bin/cs

    Some of the violations can be automatically fixed by running:

    .. code-block:: bash

        $ vendor/bin/phpcbf

Function Deceleration Order
---------------------------

Declare private functions below their first usage.

Write methods in a step-down approach (like an execution stack), first the "main"
function (like ``__construct``) followed by the method(s) that are called by this
function. Note that if these methods call other functions these come directly
after this method, and then continue with the private methods of the main
function.

.. code-block:: text

    function __constructor
        calls parseSchema
        calls parseArgumentStrings

        private function parseSchema
            calls parseSchemaElement

            private function parseSchemaElement
                calls validateSchemaElementId

            private function validateSchemaElementId

        // (notice that this comes after the previous private methods)
        private function parseArgumentStrings

        // Continue with the other methods (public first)

The exceptions to this rule are the ``setUp()`` and ``tearDown()`` methods
of PHPUnit tests, which must always be the first methods to increase
readability;

Licensing Header
----------------

Park-Manager is released under the Mozilla Public License Version 2.0 license,
and the license block has to be present at the top of every PHP file,
before the namespace.

.. code-block:: php

    <?php

    declare(strict_types=1);

    /*
     * This Source Code Form is subject to the terms of the Mozilla Public
     * License, v. 2.0. If a copy of the MPL was not distributed with this
     * file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    namespace ParkManager;

.. _service-naming-conventions:

Service Naming Conventions
--------------------------

.. tip::

    Use the class name as service-id for private and and tagged services.
    Public services should only use developer friendly names as described below.

* A service name contains groups, separated by dots;

* All Park-Manager services use ``park_manager`` as first group;

* Use lowercase letters for service and parameter names;

* A group name uses the underscore notation;

Routing Naming Conventions
--------------------------

* A route name contains groups, separated by dots;

* All Park-Manager routes use ``park_manager`` as first group,
  the module name (except for core) as second group,
  and optionally the section as third;

* The last group always revers to the action (either ``ftp_user_list``
  or ``ftp_user_register``);

* Use lowercase letters for names;

* A group name uses the underscore notation;

**Examples:**

* ``park_manager.admin.security_login``
* ``park_manager.admin.security_confirm_password_reset``
* ``park_manager.webhosting.client.account_list``
* ``park_manager.webhosting.client.ftp_user_list``

.. _`Yoda conditions`: https://en.wikipedia.org/wiki/Yoda_conditions
