Backward Compatibility Promise
==============================

Park-Manager follows a versioning strategy called `Semantic Versioning`_. It means that
only major releases include BC breaks, whereas minor releases include new features
without breaking backwards compatibility.

.. warning::

    Park-Manager is the pre-alpha development stage.

    We release minor version before every larger change, but be prepared for
    Backward Compatibility breaks to happen until a v1.0.0 release.

Since Park-Manager is based on Symfony, our BC promise extends `Symfony's Backward Compatibility Promise`_
with a few new rules and exceptions stated in this document.

Minor and patch releases
------------------------

Patch releases (such as 1.0.1, 1.0.2, etc.) do not require any additional work
apart from cleaning the Symfony cache.

Minor releases (such as 1.1.0, 1.2.0, etc.) require to run database migrations.

Code covered
------------

This BC promise applies to all of Park-Manager' PHP code except for:

    - code tagged with ``@internal`` tags
    - event listeners
    - model and repository interfaces
    - PHPUnit tests (located at ``src/**/Tests/``)
    - Behat tests (located at ``src/**/Behat/``)

Additional rules
----------------

Models & model interfaces
~~~~~~~~~~~~~~~~~~~~~~~~~

In order to fulfill the constant need to evolve, models are excluded
from this BC promise. Methods may be added to models.

Repositories & repository interfaces
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Following the reasoning same as above and due to technological constraints,
repository interfaces are also excluded from this BC promise.

Event listeners
~~~~~~~~~~~~~~~

They are excluded from this BC promise, but they should be as simple as
possible and always call another service. Behaviour they're providing
(the end result) is still included in BC promise.

Routing
~~~~~~~

The currently present routes cannot have their name changed, but optional
parameters might be added to them. All the new routes will start with
``park-manager.`` prefix in order to avoid conflicts.

Services
~~~~~~~~

Services names cannot change, but new services might be added with ``park_manager.`` prefix.

.. note::

    For autowiring make sure to reference the interface (if any) and not a
    solid implementation.

Templates
~~~~~~~~~

Neither template events, block or templates themselves cannot be deleted or renamed.

Deprecations
------------

From time to time, some classes and/or methods are deprecated in Park-Manager;
that happens when a feature implementation cannot be changed because of
backward compatibility issues, but we still want to propose a "better"
alternative. In that case, the old implementation can simply be **deprecated**.

A feature is marked as deprecated by adding a ``@deprecated`` phpdoc to
relevant classes, methods, properties, ...::

    /**
     * @deprecated since version 1.8, to be removed in 2.0. Use XXX instead.
     */

The deprecation message should indicate the version when the class/method was
deprecated, the version when it will be removed, and whenever possible, how
the feature was replaced.

A PHP ``E_USER_DEPRECATED`` error must also be triggered to help people with
the migration starting one or two minor versions before the version where the
feature will be removed (depending on the criticality of the removal).

See `Symfony's Deprecations Convention`_ on how to apply this deprecation logic.

.. _Semantic Versioning: http://semver.org/
.. _Symfony's Backward Compatibility Promise: https://symfony.com/doc/current/contributing/code/bc.html
.. _Symfony's Deprecations Convention: https://symfony.com/doc/current/contributing/code/conventions.html#deprecations
