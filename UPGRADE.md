UPGRADE
=======

## Upgrade FROM 0.1.0 to 0.2.0

### Model

* Usage of Prooph is dropped, Command/Query and Handlers no longer need to extend
  or implement a base interface/class.

* The `QueryResponseNegotiator` is removed, Query Handlers are now expected
  to return there result directly without `React\Promise`.
  
* The `AggregateRoot` interface is removed.

* The `CommandHandler` interface is removed.

* The `Util\EventsExtractor` class was removed.

* The `DomainMessageAssertion` class was removed.

* The `EventsRecordingAggregateRoot` class was renamed to `EventsRecordingEntity`.

* The `EventsRecordingEntityAssertionTrait` was renamed to `EventsCollectionTrait`.

* The `EventsCollectionTrait` was moved to the `Event` namespace.

* Command, Query and Domain Events are now simple DTO objects without a Payload,
  it's still possible to make them "exportable" but this is no longer a hard requirement.
  
### User

* The `UserIdTrait` was removed.
