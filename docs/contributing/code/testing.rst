Testing Methodology
===================

Park-Manager has fully adapted test driven development.

Because one testing framework doesn't necessarily work as good as the
other for a specific situation Park-Manager uses a number of testing
frameworks.

This sections explains the basic principles of test driven development
and standards that are followed within Park-Manager.

Introduction
------------

In short "test driven development" or TTD means you write your
tests before you write the implementation, but why?

First you need to understand that the only reason why software exists,
is to solve a specific problem. A problem can be anything,
from (long distance) communicating to filling your taxes.

* Email was invented to solve the "problem" of long distance communication;

* Reusable Frameworks were created to solve the "problem" of constantly
  repeating ourselves in solving a similar problem;

You get the picture. Now, your tests are an abstraction of the problem
you are solving. The tests describe in short what the problem includes.

    Say you are creating a Forum system to allow people to communicate
    with each other. But you don't want to allow anonymous posts,
    as this makes it harder to ban people or inform them about new messages.

    Your problem is the absence of a user-system. Now before you start coding,
    think really hard about what is needed. Don't focus on nice-to-have features,
    as they only clutter the spectrum. This is called the analyzing *or* learning stage.

    **Note:** At first you actually *don't now* you need a user-system, the
    user system itself emerges from the solution.

    Before a user can post they first need to sign-in (or login), but this requires
    an account to exist. You know discovered you need a registration system.

    To absence of a registration system is called a "Problem Domain",
    you then abstract this problem "Problem Domain" into a test (or use-case).

    Run your newly created test, and see it fail. Why? Because there is no code
    to make the test pass. *The failing of your tests confirms your tests
    can only work when an implementation (or solution) is in place.*
    If your test passes it means the test is broken, RED is good in this stage.

    Now you need to make your test(s) pass (GREEN stage), add the implementation
    and run your tests. Does it pass? Great! good yob.

    If your tests don't pass, then fix the implementation and run your tests again.
    And repeat this process until they are green.

    Do all the tests pass? Wonderful. Now repeat this RED-GREEN process until
    you no longer have any "problems" that need "solutions". And you you end-up
    with fully tested code, that will never contain more then needed.

    *No solutions unless there is a clear problem.*

**Or shorter:**

    When you write your tests before you write the implementation,
    the only logical reason why your code exists is to make your tests pass.

    You will never write code that has no tests, your tests are the abstraction
    of the "problem", your code is the solution to that problem.

    By writhing your tests first you can be certain that the tests only
    pass with working code, and not because they are not testing anything.

But wait, there is one more stage between the RED-GREEN process, the "blue" phase.

The "blue" (or refactoring phase) is a moment between finding and fixing problems,
and taking a look at the existing code and see what could be improved. After every
improvement, run your tests to make sure nothing got broken.

.. caution::

    During the refactoring phase don't add new tests or remove existing tests.
    This phase is about improving the code, not about solving the "problem domain".

    And during this cycle **only write one test and solution at a time**, the
    longer the time between coding and testing the higher the risk of creating
    a hard to find problem.

    The same applies for refactoring. *If your test fail after a small change
    you can be certain only that change caused the failure. The bigger your
    refactoring the more time you will spend debugging, and less on getting
    work done.*

Principles
----------

* RED is good, add or fix the code to make it green;

* RED-GREEN-REFACTOR is the rule;

* Don't prefix ``it`` block descriptions with ``should``. Use `Imperative mood`_
  instead;

* Don't mock a type you don't own! (see below);

* If your specification is getting too complex, the design is wrong,

  try decoupling a bit more;

* ``shouldBeCalled`` or ``willReturn``, never together, except for builders;

* If you cannot describe something easily, probably you should not be

  doing it that way;

Lastly, tests can only prove the is code is "wrong", they cannot prove the
code is completely "correct", the more variations are used to greater the
change of proving the code is wrong. But watch out for over testing (see below).

Acceptance Tests
----------------

An Acceptance test, tests that a specific functionality as a bigger whole is
working as described.

Use `Behat`_ for StoryBDD and always write new scenarios when adding a feature,
or update existing stories to adapt to business requirements changes.

*Remember you are writing a "business requirement" that describes "what"
the requirement is, not "how" the requirement is fulfilled.*

* Avoid scenario titles that repeat the feature title;

* Use scenario titles that describe the success and failure paths;

* Avoid describing technical actions (see `Introducing Modelling by Example`_);
    * "When I click"
    * "And I go to the "/basket" page"

* Use the ``features`` directory to store feature specs;

Unit Tests
----------

* `PhpUnit`_ is used for unit, integration, API acceptance and functional tests;

* Use an integration testCase class when performing integration tests;

* Unit tests must be small, easy to understand and fast (less then a minute per test);

* Mark functional tests with ``@group functional``;

* Use `descriptive assertions <https://matthiasnoback.nl/2014/07/descriptive-unit-tests/>`_

* Don't use PHPUnit to run performance suites, use `PHPBench`_ for performance suites;

Use `mutation testing <https://infection.github.io/>`_ find to missing tests.

.. caution::

    Mutation Testing should not be used on functional or acceptance tests
    but on unit and integration tests only.

Smoke Tests
-----------

Infrastructure details which heavily dependent on external factors (that
cannot be replicated) may be tested with a smoke test.

Unlike a unit test, a smoke test covers a big part of the code and tests
it as a whole. When there is an error ("smoke"), this indicates a failed
test. Otherwise, we can assume our code works as expected.

A smoke test usually doesn't perform any assertions but runs the code
and uses the code's own error reporting as sign of failure.

Over-testing
------------

Avoid over-testing, which is best explained by the following article from Mathias Verraes;

    Figuring out how much unit tests you need to write, can be tricky,
    especially if you are new to Test-Driven Development.

    Some teams strive for 100% code coverage.
    Some open source projects even announce their test coverage on their GitHub profiles
    – as if coverage is an indicator of quality.

    Coverage only measures the lines of code that are executed by the test suite.
    It doesn’t tell you whether the outcome of the execution is actually tested,
    let alone how valuable that test is.

    Mathias Verraes 2014 - http://verraes.net/2014/12/how-much-testing-is-too-much/

Tests become a problem when:

* they are slow;
* they need to be changed all the time;
* they break often;
* they are hard to read;
* … or they bother you in some other way;

When any of those occur, the tests need to be inspected.
Now is the time to decide whether you want to refactor the test itself,
or refactor the code under test, or, in some cases, remove the tests.

Low-value tests are usually harmless.
There’s no urgent need to decide upfront whether they need to be deleted.
Trust your instinct, or in this case, your annoyance level.

Don't mock a type you don't own!
--------------------------------

*This is not a hard line, but crossing this line may have repercussions! (it most likely will)*

1. Imagine code that mocks a third party library. After a particular upgrade of a third library,
   the logic might change a bit, but the test suite will execute just fine, because it's mocked.
   So later on, thinking everything is good to go, the build-wall is green after all,
   the software is deployed and... *Boom*

2. It may be a sign that the current design is not decoupled enough from this third party library.

3. Also another issue is that the third party lib might be complex and require a lot
   of mocks to even work properly. That leads to overly specified tests and complex
   fixtures, which in itself compromises the *compact and readable* goal.

   Or to tests which do not cover the code enough, because of the complexity
   to mock the external system.

Instead, the most common way is to create wrappers around the external lib/system,
though you should be aware of the risk of *abstraction leakage*, where too much
low level API, concepts or exceptions, goes beyond the boundary of the wrapper.

In order to verify integration with the third party library, write integration tests,
and make them as *compact and readable* as possible as well.

Other people have already written on the matter and experienced pain when
mocking a type they didn't own:

* http://davesquared.net/2011/04/dont-mock-types-you-dont-own.html
* http://www.markhneedham.com/blog/2009/12/13/tdd-only-mock-types-you-own
* http://blog.8thlight.com/eric-smith/2011/10/27/thats-not-yours.html
* http://stackoverflow.com/questions/1906344/should-you-only-mock-types-you-own

Don't mock everything, it's an anti-pattern
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If everything is mocked, are you really testing the production code?
Don't hesitate to **not** mock!

Don't mock business logic
~~~~~~~~~~~~~~~~~~~~~~~~~

Business logic or domain logic is the part of the program that encodes
the real-world business rules that determine how data can be created,
displayed, stored, and changed.

In practice Business logic includes (but is not limited) to ValueObjects,
AggregateRoot/Entity, Domain messages, event objects and data Models.

In most cases it should not be possible in the first place to mock these objects
as they are marked final.

Why shouldn't you mock this logic? **Because its not an interface!** Business logic
describes some very specific rules about the application, logic that must
(*not should*) be followed strictly!

If it's too difficult to create new fixtures, it is a sign the code may need some
serious refactoring. An alternative is to create builders for your value objects.
You can also create meaningful factory methods in the test.

Originally based on: https://github.com/mockito/mockito/wiki/How-to-write-good-tests

Test naming rules
-----------------

A test ensures something is possible with the subject, it "can do" or
"does something". It does not describe "what" a subject does or is
"described" to do.

Name your tests like you name your methods: short, descriptive and explicit.

.. tip::

    A sentences with "and" or "then" *could* an indication the test is doing to much.

* Avoid using articles: "the", "a" "an", "then";
* Prefer using "when" instead of "if";

Unit tests
~~~~~~~~~~

In unit tests the test-class itself always corresponds to the class
that is being tested (the subject under the test).

Prefer using the ``it`` notation for a test name.

.. note::

    Because there is no hard contract (test does not describe what the subject does),
    it's acceptable to use "should" like ``ShouldReadColorsWhenFalseInConfigurationFile``.

**Some examples on how to compose a unit test name:**

* ``[property] can be [actioned]``;
* ``it [throws, renders, connects, etc.] when [condition] [in, is] [expected condition result]``;
* ``[subject property/information] is [perform expected. like: read correctly, written correctly]``
* ``it can be [actioned] [to, with, from, in, etc] [object]``;

**A ValueObject or Entity should use the `it` notation**:

* ``it [actions] [property]``;
* ``it will throw when [condition]``;
* ``its a [type name]``;

**Final examples:**

* ConfigurationTest:

    * ``Listener Configuration is read correctly``;

* MoneyTest:

    * ``its amount can be retrieved``;
    * ``its currency can be retrieved``;
    * ``it allows another money object with the same currency``;
    * ``it can subtract another money object with same currency``;
    * ``it can be negated``;
    * ``it can be multiplied by a factor``;
    * ``it can be allocated to number of targets``;
    * ``it can be allocated by ratios``;
    * ``it can be compared to another money object with same currency``;

* DateTimeTypeTest:

    * ``it can be created``;
    * ``its ViewTimezone can be transformed to ModelTimezone``;
    * ``its should fail transformation for invalid input``;
    * ``it can configure time pattern`` (alternative: ``its time pattern is configurable``);
    * ``its pattern can be configured``;

* UserIdTest:

    * ``its an identity``;
    * ``it is convertible to a string``;
    * ``it is comparable to another object``;

Credits
-------

This document is composed from information provided by external sources,
if any credits are missing please update this document by opening a
pull request, thank you.

.. _`Imperative mood`: http://en.wikipedia.org/wiki/Imperative_mood
.. _`Behat`: http://docs.behat.org/
.. _`Introducing Modelling by Example`: http://everzet.com/post/99045129766/introducing-modelling-by-example
.. _`PhpUnit`: https://phpunit.de/
.. _`PHPBench`: https://github.com/phpbench/phpbench
