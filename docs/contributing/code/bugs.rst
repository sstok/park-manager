Reporting a Bug
===============

Whenever you find a bug in Park-Manager, we kindly ask you to report it. It helps
us make a better hosting system.

.. caution::

    If you think you've found a security issue, please use the special
    :doc:`procedure <security>` instead.

Before submitting a bug:

* Double-check the official :doc:`documentation </index>` to see if you're not
  misusing the system;

* Check if your problem relates to Park-Manager or a third-party library,
  if your problem doesn't directly relate to Park-Manager, report it to
  the external project instead;

* Ask for assistance on `Stack Overflow`_, on the #support channel of
  `the Park-Manager Slack`_  if you're not sure if your issue really is a bug.

If your problem definitely looks like a bug, report it using the official bug
`tracker`_ and follow some basic rules:

* Use the title field to clearly describe the issue;

* Describe the steps needed to reproduce the bug with short code examples
  (providing a unit test that illustrates the bug is best);

* Give as much detail as possible about your environment (OS, Docker version,
  PHP version, Symfony version, Park-Manager version, enabled extensions,
  used modules, ...);

* If you want to provide a stack trace you got on an HTML page, be sure to
  provide the plain text version, which should appear at the bottom of the
  page. *Do not* provide it as a screenshot, since search engines will not be
  able to index the text inside them. Same goes for errors encountered in a
  terminal, do not take a screenshot, but copy/paste the contents. If
  the stack trace is long, consider enclosing it in a `<details> HTML tag`_.

  **Be wary that stack traces may contain sensitive information, and if it is
  the case, be sure to redact them prior to posting your stack trace.**

* *(optional)* Attach a :doc:`patch <patches>`.

Bug handling
------------

In the issue tracker there are two types of bug reports:

* **Bug** - Confirmed bugs or bug fixes.
* **Potential Bug** - Bug reports, should become a *Bug* after confirming it.

A "Potential Bug" needs to be confirmed by the Core Team or another Contributor
(preferably by providing a minimal reproduction of the reported issue),
before becoming a confirmed bug.

.. note::

    Spelling/grammar corrections and styling violations are not marked as a Bug.
    These are marked as an **Enhancement**.

Any Potential Bug that cannot be reproduced (with the provided information)
is marked as **Help Wanted**, if no new information is provided within a reasonable
time period the issue is marked as **Stale**. And thereafter closed when no activity
is reported after two weeks.

.. caution::

    Except for syntax error fixes; a proposed patch should always have a
    test-case to proof the patch fixes the reported bug and to prevent
    future regressions.

    Bug-fix pull requests without a test-case are not be merged unless
    a test is technically impossible or other measures are taken to
    prevent future regressions.

.. _`Stack Overflow`: http://stackoverflow.com/questions/tagged/park-Manager
.. _the Park-Manager Slack: https://park-manager.slack.com/messages
.. _tracker: https://github.com/park-Manager/park-Manager/issues
.. _<details> HTML tag: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/details
