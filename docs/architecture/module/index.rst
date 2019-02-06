Module Directory Structure
==========================

Modules are composed using the Ports and Adapters pattern
as described in `DDD, Hexagonal, Onion, Clean, CQRS, How I put it all together`_.

In short a Port is interface and an Adapter an implementation to that
interface.

Layers
------

Each Module is build-up from three main layers (inner to outward):

* The :doc:`Domain </architecture/module/domain>` which forms the hart of a Module.
  As explained in :doc:`Introduction to DDD </architecture/ddd_introduction>`
  this layer contains all the Entities, Value Objects, Domain Events and
  Repository interfaces.

  Whenever you create a new Module this is your starting point.

* The :doc:`Application Layer </architecture/module/application>` which operates
  on-top of the Domain and provides a number of business related use-cases.

  All top layers and other Bounded Contexts only communicate with the Domain
  through this layer. **Direct communication with the Domain layer is prohibited.**

  Here you can find all the Commands, Queries, there handlers, Application Services
  and Finder interfaces.

* The :doc:`Infrastructure </architecture/module/infrastructure>` which provides
  opinionated implementations to the interfaces defined in deeper layers.

  Here you can find the Symfony framework integration, persistence adapters,
  templates, translation files and other resource files.

  This is also where the :doc:`user interfaces </architecture/module/infrastructure/user_interface/index>`
  are kept.

.. _`DDD, Hexagonal, Onion, Clean, CQRS, How I put it all together`: https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together/
