Maintenance Rules
=================

This document explains the rules for maintaining the Park-Manager project (that is the
code & documentation hosted on the main ``park-manager/park-manager`` `Git repository`_).

These rules are specific to Core Team Members as listed in :doc:`/contributing/organization/core-team`.

Common rules
------------

#. Configure your global Git to sign all commits (and tags);
#. Don't commit changes directly to a main repository branches,
   for example don't push to the master branch directly. Use pull requests instead;
#. Don't create new release unless being asked by the Project Leader;
#. Only merge pull requests using `HubKit`_, and follow the :ref:`pull-request-checklist`
#. Don't change GitHub repository labels (https://github.com/park-manager/park-manager/labels) and topics
   unless being asked by a Project Leader or Lead Developer;
#. Don't close an issue/pull request unless a proper reason was given;
#. Don't lock and issue/pr unless it violates the `Code of Conduct`_ or is a security patch
   provided by the Core Team;

.. note::

    In exception to rule nr. 2 small CS/spelling fixes to a recently
    merged pull request are allowed but should be avoided.

    Any functional changes must always be provided with a pull request.

Releases
--------

New releases are coordinated by the Project Project Leader.
Core Members handling a release are revered to as Release Managers.

.. note::

    Issues and pull request are managed using milestones, when a release
    is created for a specific milestone (for example ``1.2``) all open pull request
    and issues must be moved to the next possible milestone (for example ``1.3``).

Creating a new release
~~~~~~~~~~~~~~~~~~~~~~

Before any release is created, the following things need to be confirmed:

#. The correct branch is checked-out;
#. A patch release contains only bug fixes and no new features;
#. The UPGRADE instructions are up-to-date and properly formatted;
#. The release title doesn't violate any registered trademarks
   and has not been used previously.

Releases are created using `HubKit`_ which ensures:

* The Git tag is signed;
* A GitHub release page is created (with a proper changelog);
* Split repositories are synchronized;
* Version numbers are continuous;

To create a new release simply run ``hubkit release`` followed
by the version number or ``patch``/``minor``/``major`` respectively.

.. code-block:: console

    $ hubkit release minor

This process might take some time as all split repositories are updated
and tagged, existing tags in split repositories are automatically ignored.

.. _`Git repository`: https://github.com/park-manager/park-manager
.. _`Hubkit`: http://www.park-manager.com/hubkit/
.. _`Code of Conduct`: https://github.com/park-manager/park-manager/blob/master/CODE_OF_CONDUCT.md
