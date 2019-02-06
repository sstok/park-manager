Application Layer
=================

Commands
--------

TBD.

Query
-----

TBD.

Service
-------

DomainObjectFactory
~~~~~~~~~~~~~~~~~~~

Command handlers should avoid hardcoding the Entity creation, as this makes
it harder to use a different Entity. _Only Entities marked as `@final` don't
require the usage of a factory service. And neither do final ValueObjects._

Instead handlers should use a factory to create new entities, which makes
no assumptions about the required arguments (and their types). The arguments
are provided as an array and then passed to a configured constructor method.

**Note:** The "default" factory defines what is always required, but the
a replacement factory must still respect the original contract and try
to adapt to this as natural as possible.

Validation
~~~~~~~~~~

The Domain follows a strict rule: No invalid data is accepted.
*It is impossible to change the status to an unsupported value.*

    A password that must to follow some specific rules (like a strength enforcement)
    should be validated *before* being passed to the Domain;

    These constraints do not apply to the Domain, and therefor don't belong here.

Secondly, the Domain must disallow any operation that would compromise
the integrity of it's data;

For example to reset a user password a specific process needs to be followed:

1. Request a reset token;

2. Send the reset token to the user (handled by the Infrastructure);

3. The Domain is asked to reset the password; But this is only possible
   when a password reset was actually requested, the token did not expire,
   *and* the provided token is valid;

All these Invariants (except point 2) are performed within Domain!
Performing this logic outside the Domain could lead to bypassing this process
and introducing security issues.

.. note::

    Use DTOs (Data transfer objects) to transport information that doesn't
    follow strict constraints. *Command and Query messages are considered DTOs.*

There is however one exception to this rule, some constraints can only
be applied afterwards (before persisting the Domain's current state).

To allow for flexibility in this is delayed validation a custom validator
is used; The Validator is passed to the Model and the Model invokes the
validator service::

    $model->validate($validationService);

The Model must use the validators results to determine if the current state
is valid, and throw an exception if the Model's state is invalid.

.. note::

    Authorization access and "package" capabilities validation is performed
    by the ServiceBus, *before* the Message is handled;

    Only when the "final" result of the Model is needed for validation,
    this is performed by the Domain.

Service
~~~~~~~

Application Services apply to the ServiceBus, and some are triggered by
listening for Domain Events.

Infrastructure Services provide implementations for Application interfaces,
including Mailing, Reporting and 3rd-party gateway access.

Infrastructure Services provide technology specific implementations like
mailing, or filesystem abstraction.

TBD.

Finder
------

TBD.
