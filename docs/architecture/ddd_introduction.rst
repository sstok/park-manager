Introduction into Domain Driven Design
======================================

Before you get started understand this properly:

The Domain is central to the application, it doesn't communicate
with other systems directly. Details like persistence or database
look-ups are not a part of a Domain.

Domain Driven Design
--------------------

In DDD information "Domains" are separated by boundaries (Bounded Contexts
or BC for short), within each BC, terminology (Terms) have a well defined
meaning; "Size" for example can relate to the overall account size or size
of someones user-picture, to prevent confusion each BC explicitly declares
the meaning of a Term "within that context".

Information maybe shared across boundaries, but each BC has it's own
understanding of this information. So information maybe transformed
before being shared with another boundary, and the shared information
should be immutable (*the object cannot be changed after creation*) to
prevent a change in one BC to directly affect another boundary.

Secondly, communication is more natural and tailored towards communicating
an action, rather then forcing a state upon an object;

* Do you *set* a password, or do you *change* a password?

* Do you *create* a new user, or do you *register* a user?

Or in other words, avoid using setters and use more descriptive methods
instead. ``changePassword()`` not ``setPassword()``, and ``disable()``/
``enable()`` not ``setEnabled()``.

A Domain combines all this knowledge in a BC, called the Information
Domain, or Domain for short. A Domain is all about knowledge (*what is
a "User"), and which constraints apply within this Domain (*a User has
an unique ID*), and less about external details like the user interface,
data persistence or (audit) logging.

Value Objects
-------------

A value object is a small object that represents a simple entity whose equality
is not based on identity: i.e. two value objects are equal when they have the
same value, not necessarily being the same object.

Examples of value objects are objects representing an amount of money
or a date range. ``DateTimeImmutable`` is also a Value Object.

.. note::

    A User might be a value object, depending on the context of usage.

    The main difference between entities and value objects, is that
    an entity is always uniquely identified by an ID or natural key
    (email address).

    Having two Users with the same email address is only acceptable
    if the constraints of the Domain allow this.

    If the User in this context is only used to provide generic information
    (like a profile) but the User is never stored as something that
    is uniquely identified we can tread the User as an Value object.

    The row of invoice is a good candidate for a Value Object,
    the information might be shared with multiple orders, but only the
    Order Entity knows about which rows it holds. The Rows know nothing
    about the Order they were assigned to.

Domain Events
-------------

When an Entity changes it state, it may produce Events to inform other
layers and Bounded Contexts about important changes.

There's no golden rule when a mutation in the Entity should produce an Event.

Anything that is of importance to the Domain usually produces an Event,
and allows other systems and Bounded Contexts to react to this Event.

*When in doubt don't fire any events.*

.. note::

    An Entity that produces events is most times referred to as an Aggregate,
    but because the technical details of an Aggregate are a bit more difficult
    we'll stick with the term Entity for the remainder of this of manual.


    There's no golden rule when a mutation in the Entity should produce an
    Event. It's hard to know upfront which changes should produce an Event.

    But instead of producing Events for *ever* mutation, they are introduced
    once there is a valid use-case, even if that use-case is limited to
    an single extension.

A few things about Events:

* Events should be small, they only contain information relevant to what happened;

* Events may be dispatched to other Bounded Contexts;

* Events are dispatched *after* the changes occurred;

* Events are immutable, and therefor don't hold any references to mutable objects,
  like entities or services.

.. caution::

    **Don't use Events to enforce Business Rules or integrity within the
    same Bounded Context!**;

    There is no guaranteeing that events are actually handled or handled in
    the correct order, events are informative and should be threaded as such.

Command Query Responsibility Segregation
----------------------------------------

Command Query Responsibility Segregation (or CQRS for short) is about
separating the write and read side using a Message Bus.

In traditional applications the information is stored and provided from
the same service, a Repository, usually powered by an Object Relationship
Mapping system (or ORM for short).

If you worked with Symfony before you'd properly also know `Doctrine ORM`_,
you create a new Entity object, persist and flush the object to the database,
and then later fetch this same object using a Repository. Then you pass
the fetched Entity to the Controller and view (Twig template).

This approach however has some drawbacks. Doctrine ORM is great for mapping
objects to and from a database scheme, but hydrating these records to objects
is performance intensive and you may need not all fields or need them in
a different format.

For high-traffic websites it's common to use a read optimized solution
like Elasticsearch or a de-normalized data storage. But now your application
needs to know about these details.

Separating Read from Write
~~~~~~~~~~~~~~~~~~~~~~~~~~

CQRS separates the Read (Query) side from the Write (Command) side, using
a Message Bus.

*Instead of directly using a Repository service the Controller (or user interface
to be specific) dispatches a Message to a MessageBus which will either
update the Entity (Command) or Return a fetched result (Query).*

Each Domain Message (Message) is routed to a specific handler service that
will handle the Message. Only the handlers communicate with the Repository.

All other communication is handled using a Message Bus.

This in practice allows you to use a different system for the Query and
Command handlers. The Query Message Bus (QueryBus for short) can use an
Elasticsearch index while the Command Message Bus uses Doctrine ORM.

All the details are hidden (or encapsulated), so you only communicate through
Messages.

Sounds familiar? The HTTP protocol we all use on a daily basis works very
similar, as a visitor we don't directly fetch a file from a server but
dispatch an HTTP request at which the server sends a response.

At any given moment we can replace the Message handler with a different
implementation, as long as we still return the same structure.

But there is more, before the message is Handled we can perform a number
of operations (called Middlewares), including authorization checking,
logging and encapsulating the whole operation in a transaction.

All while keeping a clear separation and decoupling of implementations.

The Messages and there handlers are part of the Application layer,
for each business use-case there is exactly one message.

While not formally required the Messages themselves should be immutable,
the holder (or Envelope) in which they are transported is heavily dependent
on the implementation of the Message Bus.

.. note::

    There are some misconceptions about CQRS and it's usage, CQRS is mainly
    about separating the Read (Query) and Write (Command) side of an application
    using a Message Bus, nothing more. *Message Serializing, Event Sourcing,
    multi-tier storage and async handling are all additions, not requirements!*

    A CommandBus usually follows a dispatch and forgot approach.

    If you need any information for say a redirect you need to compute
    this first, and pass it to the Command before dispatching.

    In the Park-Manager system Messages are not serialized for backend
    processing unless mentioned otherwise. *Domain Events are not stored,
    and projections are only used for situations were performance cannot
    be solved otherwise.*

Other Terms and Legend
----------------------

In Domain Driven Design there are number of other other terms, for clarity
you can find all there definitions here.

* **Actor**: A user (in general terms) or system-process performing an action
  within the system. Either an Administrator, Client or a background process.

* **MessageBus**: Handles Domain Messages, see also
  https://symfony.com/doc/current/messenger.html

* **Bounded Context**: Creates boundaries between information models's.

  One Bounded Context cannot directly access data from another Bounded Context,
  but must use the provided APIs (Message Bus) for fetching/sending information.

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
  set of Actors (the reporter, support administrator, and a list of selected
  collaborators).

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
  the object in an invalid state (a date is always "valid"), an ID
  is of the correct type/format, and a status change never by-passes
  the organization's workflow (``Active -> Concept`` is rejected).

Further Reading
---------------

Now that you understand the basics of DDD it's time to learn about
the Park-Manager :doc:`Modules </architecture/modules>` system, and how all
this is composed.

.. _`Doctrine ORM`: http://www.doctrine-project.org/projects/orm.html
