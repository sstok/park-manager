UPGRADE
=======

## Upgrade FROM 0.4.0 to 0.5.0

 * The Park-Manager Components Bridges and Bundles have all been merged
   into the CoreModule.
   
  * The SplitToken functionality is now available at https://github.com/rollerworks/split-token
  
  * Modules are now expected to extend from the 
    `ParkManager\Module\CoreModule\Infrastructure\DependencyInjection\Module\AbstractParkManagerModule`
    class or at least implement the `ParkManagerModule` otherwise.
    
  * The ServiceBus component and bundle were replaced with the Symfony Messenger.

## Upgrade FROM 0.3.0 to 0.4.0

 * The Model Component has been removed, use the SharedKernel and 
   ApplicationFoundation Components instead.
   
### Security

 * A `SplitToken` can not be created directly anymore, use one of the provided
   Factories for creating a `SplitToken`.
   
   Tip: Use `FakeSplitTokenFactory` for testing, this implementation uses a
   static value for all tokens and performs no actual password-hashing.
   
   Before:
  
   ```php
   $token = SplitToken::generate($id);
   $token = SplitToken::fromString(...);
   ```
   
   After:
   
   ```php
   $splitTokenFactory = new SodiumSplitTokenFactory(); // No special options.

   $token = $splitTokenFactory->generate($id);
   $token = $splitTokenFactory->fromString(...);
   ```
   
 * The `SplitTokenValueHolder` can not be used anymore to validate
   a SplitToken, use the `SplitToken` object to validate:
   
   Before:
  
   ```php
   if ($holder->isValid($token, $id)) {
       // ...
   }
   ```
   
   After:
   
   ```php
   if ($token->matches($holder, $id)) {
       // ...
   }
   ```

## Upgrade FROM 0.2.0 to 0.3.0

### User

 * The `UserCollection::getByEmailAddress()` method has been renamed 
   to `findByEmailAddress`.

 * The `UserCollection::getByEmailAddressChangeToken()` method has been renamed 
   to `findByEmailAddressChangeToken`.

 * The `UserCollection::getByPasswordResetToken()` method has been renamed 
   to `findByPasswordResetToken`.
  
### Webhosting

* The `WebhostingAccountOwner` class has been replaced by `RootEntityOwner` 
  of the Model Component.
  
* The `Model\DomainName\WebhostingDomainNameRepository::getByFullName()` method has
  been renamed to `findByFullName`.

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
