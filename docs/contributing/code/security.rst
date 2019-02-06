Security Policy
===============

This document explains how Park-Manager security issues are handled by the Park-Manager
core team (Park-Manager being the code hosted on the main ``park-manager/park-manager`` `Git
repository`_).

.. _reporting-a-security-issue:

Reporting a Security Issue
--------------------------

If you think that you have found a security issue in Park-Manager, don't use the
bug tracker and don't publish it publicly. Instead, all security issues must
be sent to **security [at] rollerscapes.net**. Emails sent to this address are
forwarded to the Park-Manager core-team private mailing-list.

.. note::

    While we are working on a patch, please do not reveal the issue publicly. The resolution can take
    anywhere between a couple of days, a month or an indefinite amount of time depending on its complexity.

Resolving Process
-----------------

For each report, we first try to confirm the vulnerability. When it is
confirmed, the core-team works on a solution following these steps:

#. Send an acknowledgement to the reporter;
#. Work on a patch;
#. Get a CVE identifier from `mitre.org`_;
#. Write a security announcement for the official Park-Manager `blog`_ about the
   vulnerability. This post should contain the following information:

   * a title that always include the "Security release" string;
   * a description of the vulnerability;
   * the affected versions;
   * the possible exploits;
   * how to patch/upgrade/workaround affected applications;
   * the CVE identifier;
   * credits.
#. Send the patch and the announcement to the reporter for review;
#. Apply the patch to all maintained versions of Park-Manager;
#. Package new versions for all affected versions;
#. Publish the post on the official Park-Manager `blog`_ (it must also be added to
   the "`Security Advisories`_" category);
#. Update the security advisory list (see below).
#. Update the public `security advisories database`_ maintained by the
   FriendsOfPHP organization and which is used by the ``security:check`` command.

Classification of Security Issues
---------------------------------

This section explains how we classify security issues that are discovered
in Park-Manager.

Critical
~~~~~~~~

A critical rating applies to vulnerabilities that allow remote,
unauthenticated access and code execution, with no user interaction required.

These would allow complete system compromise and can easily be exploited
by automated scripts such as worms.

Important
~~~~~~~~~

An important rating applies to vulnerabilities that allow system authentication
levels to be compromised.

These include allowing local users to elevate their privilege levels,
unauthenticated remote users to see resources that should require
authentication to view, the execution of arbitrary code by remote users,
or any local or remote attack that could result in an denial of service.

Moderate
~~~~~~~~

A moderate rating applies to vulnerabilities that rely on unlikely scenarios
in order to compromise the system.

These usually require that a flawed or unlikely configuration of the system
be in place, and only occur in rare situations.

Trivial
~~~~~~~

A trivial rating applies to vulnerabilities that do not fit into the higher
categories.

These vulnerabilities occur in very unlikely situations and configurations,
often requiring extremely tight timing of execution and/or for events to
occur that are out of the attacker's control.

This rating may also be given to vulnerabilities that, even if successful,
impose few or no consequences on the system.

.. _Git repository: https://github.com/park-manager/park-manager
.. _blog: https://www.park-manager.com/blog/
.. _Security Advisories: https://www.park-manager.com/blog/category/security-advisories
.. _`security advisories database`: https://github.com/FriendsOfPHP/security-advisories
.. _`mitre.org`: https://cveform.mitre.org/
