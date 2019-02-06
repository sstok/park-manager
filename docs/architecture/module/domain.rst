Domain Layer
============

Single Assignment Knowledge
---------------------------

Within a Bounded Context only one side of a boundary keeps track of ownership.

In short:

* A webhosting account has an owner (UUID), the owner can be anything
  or anyone. In practice the webhosting account can even be owned by
  a group of users (Administrators).

* A webhosting account is not aware of it's cluster group or system-id, the
  clustering system keeps track of which webhosting is hosted on what system
  (knows where it's data is stored).

Linked root entities (either Account one-to-many FTPUser) are prohibited.
Relations are only realized by the usage of ID Value Objects.

Child entities are always linked to their root entity, and are are only
accessible by their root entity.

Avoid construct like: ``$invoice->getRow(1)->setPrice(...)``, use
``$invoice->replaceRow(new InvoiceRow(...))`` instead.

Domain Events
-------------

TBD.

Services
--------

TBD.

Repositories
~~~~~~~~~~~~

Repositories are responsible for tracking Entities, they are only directly
communicated by the Domain and Application layer. The User Interface layer
is prohibited from directly fetching or storing entities.

Each root Entity (the owning side if ORM terms) has it's own repository,
defined as interface.

In practice an Account may *own* multiple FTPUsers but the Account is not
directly aware of this relationship.

.. note::

    In a Domain repository only supply getter methods for each uniquely
    identifiable values (like the id, emailAddress, slug), and always throw
    an exception when no results were found. Don't return ``NULL``.
