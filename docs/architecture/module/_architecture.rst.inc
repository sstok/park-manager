.. index::
   single: architecture

System Architecture
===================

The CoreSystem and Modules follow the Domain Driven Design (DDD) principle,
and use the CQRS pattern for communication between the application layers.

If you are new to DDD and/or CQRS you definitely want read :doc:`/core/ddd_and_cqrs`
to get some basic understanding of this way of developing.

Terms
-----

Throughout this section we'll use a number of common terms, be sure
you understand these before you continue.

* **Actor**: A user or system-process performing an action within the system.
  Eg. an Administrator, Customer or a background process.

* **ServiceBus**/**MessageBus**: Handles Domain Messages, see also
  http://tactician.thephpleague.com/

* **Bounded Context**: Creates boundaries between information models's (Module).

  One Bounded Context cannot directly access data from another Bounded Context,
  but must use the provided APIs (ServiceBus) for fetching/sending information.

* **Command**: messages describe actions the Model can handle, eg. ``RegisterCustomer``.

* **Event**: messages describe things that happened while the Model handled a Command,
  eg. ``CustomerWasRegistered``.

* **Query**: messages describe available information that can be fetched from the Model,
  eg. ``GetCustomerById``.

* **Business Rules**: A business rule is a rule that defines or constrains some aspect
  of business. Business rules describe the operations, definitions and constraints
  that apply to an organization (or a Module).

  For example, a business rule might state that an webhosting account has a limitation
  on the number of mailboxes. That a Support ticket is only accessible by a limited
  set of Actors (the reporter, support administrator, and a list of selected collaborators).

* **Business logic**: also revered to as domain logic is the part of the program that
  encodes the real-world business rules that determine how data can be created, displayed,
  stored, and changed.

* **Value Object**: a value object is a small object that represents a simple
  entity whose equality is not based on identity: i.e. two value objects are
  equal when they have the same value, not necessarily being the same object.

  Examples of value objects are objects representing an amount of money
  or a date range. ``DateTimeImmutable`` is also a Value Object.

* **Invariant**: An invariant is a condition that can be relied upon to be
  true during execution of a program, or during some portion of it.

  In other words, any operation you perform on the object doesn't put
  the object in an invalid state (a date is always "valid"), an id
  is of the correct type/format, and a status change never by-passes
  the organization's workflow (``Active -> Concept`` is rejected).

Directory Structure
-------------------

Each system (either a Module) is build-up from two main parts, the Application,
which contains the actual system while being agnostic to interaction (UI)
and persistence. And the Infrastructure which provides specific implementations
for Application interfaces, Infrastructure also contains the user interface
details.

For more complex systems (like the WebhostingModule) the information Domains
are separated into smaller components, which are organized within a sub-part
(eg. the Account Model is kept in ``Application/Domain/Model/Account/``).

To get a better idea, here's an small snippet of the WebModule directory
structure:

.. code-block:: text

    * Application
        * Command
            * Account
                * RegisterAccount.php
                * RegisterAccountHandler.php
        * Query
            * Account
                * GetWebhostingAccountById.php
        * Domain
            * Finder
                * Account
                    * AccountFinder.php
                    * AccountView.php
            * Model
                * Account
                    * Account.php
                    * AccountRepository.php
                    * AccountId.php
                    * Event
                        * AccountWasCreated.php
                    * Exception
                        * AccountNotFound.php
        * Service
            * Packages
                * Capability
                    * CapabilitiesRegistry.php
    * Infrastructure
        * Adapter
            * Doctrine
                * Finder
                    * Account
                        * DoctrineDbalAccountFinder.php
                * Type
                    * DbalAccountId.php
            * DependencyInjection
                * Configuration.php
                * DependencyExtension.php
        * Resources
            * assets
                * form_theme.css
            * templates
                * account
                    * show.html.twig
            * translations
                * messages.en.yaml
            * config
                * routing
                    * client.php
                    * admin.php
                * services
                    * account.php
        * Web
            * Form
                * Type
                    * Account
                        * RegisterAccount.php
            * Action
                * Account
                    * ShowAccount.php

.. note::

    The UI cannot directly communicate with the Domain, use the Application's
    ServiceBus to query for information or commanding changes.

    Do not bind a View Model to a Form! Use a separate Form Model or Application
    Command instead.

Domain
~~~~~~

Domain is the "source of knowledge", consisting of Entities, Events,
Repository interfaces, and Value Objects. It guards the integrity of
the data-system (by enforcing Business Rules).

Communicating with the Domain happens trough a `ServiceBus <Tactician ServiceBus>`_
(and Domain Messages); Domain messages are immutable objects that either
command a mutation (Command) or requests an existing item or data-set (Query).

Both Command and Query have there own MessageBus. And each system has
it's own Command -and QueryBus instance.

.. note::

    Querying is handled by a Query handler (in the Application),
    the Query handler uses a Finder for fetching information,
    from a storage or search-index (Infrastructure).

Domain Event
............

Entities may produce Events to communicate about important changes.

.. note::

    There's no golden rule when a mutation in the Entity should produce an
    Event. It's hard to know upfront which changes should produce an Event.

    But instead of producing Events for *ever* mutation, they are introduced
    once there is a valid use-case, even if that use-case is limited to
    an single extension.

A few things about Events:

* Events should be small;

* Events may be dispatched to other Bounded Contexts (Modules);

* Events are dispatched *after* the changes occurred, but within
  the same transaction;

* **Don't use Events for enforcing Business Rules or integrity
  within the same Bounded Context!**; There is no guaranteeing
  that events are actually dispatched, or that propagation is stopped
  before reaching a ProcessManager!

* A changed Collection may produce in a new "generic" event eg. ``WhiteListWasChanged``;

.. caution::

    The Domain does not handle side-cases or *informal* logic;

    Sending a welcome message when a user registers or logging that a
    user was successfully authenticated.

    These should be handled using Domain Event listeners or a ProcessManager instead;

Validation
..........

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

ProcessManager
..............

The ProcessManager listens for Domain Events and uses these to affect the
"process" of business. Include sending (welcome) e-mails, updating the
state of a WorkFlow configuration, or allocating a unique incremental
id (which is a blocking operation).

The ProcessManager is a plug-in to the ServiceBus, and operates within
the same transaction as the current operation.

Infrastructure
~~~~~~~~~~~~~~~

The Infrastructure provides concrete implementations for interfaces defined
in the Application, plus some additional logic for integration with Park-Manager.

Exceptions and Informational Messages
-------------------------------------

Exceptions from Application follow a specific convention to allow easy
handling in the UI.

All Exceptions that extend from the ``ParkManager\Model\ConstraintException``
class are automatically communicated back to the UI. While keeping sensitive
information hidden from regular users.

Non-critical information is communicated using the
:class:``ParkManager\Component\Model\LogMessage\LogMessages``.

By using the ``park_manager.service_bus.log_messages`` service informational
messages are automatically send to the UI;

.. note::

    For the web UI the messages are converted to flash-messages,
    in a command-line application the messages are disabled as console logs,
    and for the API-Platform these messages are appended to response
    (using a custom Hydra data-structure for JSON-LD).

Further reading
---------------

Now that you know how the Park-Manager architecture is set-up you properly
want to learn how to create your own Module. Or contribute a new Module
to the Park-Manager project.

Learn more about creating a Module in :ref:`page-creation-modules`.

.. _`Symfony Framework`: https://symfony.com/
.. _`Tactician ServiceBus`: http://tactician.thephpleague.com/
.. _`Doctrine ORM`: http://www.doctrine-project.org/projects/orm.html
