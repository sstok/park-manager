Submitting a Patch
==================

Patches are the best way to provide a bug fix or to propose enhancements to
Park-Manager.

Step 1: Setup your Environment
------------------------------

Install the Software Stack
~~~~~~~~~~~~~~~~~~~~~~~~~~

Before working on Park-Manager, setup a friendly environment with the following
software:

* Git;
* Docker 17 (and DockerCompose) or above.
* Makefile

.. tip::

    The tests are run using Docker so you don't have to worry
    about additional software requirements or side affects.

    Make sure you have the latest Docker and Docker-compose installed
    (The old Docker toolbox is not officially supported and requires
    manual configuring).

If you don't have Makefile installed obtain a copy for your system
using the systems software repository. macOS can install the last
version of xCode, Windows users can either use the Linux subsystem (Windows 10)
or use `GnuWin <http://gnuwin32.sourceforge.net/packages/make.htm>`_.

Configure Git
~~~~~~~~~~~~~

Set up your user information with your real name and a working email address:

.. code-block:: terminal

    $ git config --global user.name "Your Name"
    $ git config --global user.email you@example.com

.. tip::

    If you are new to Git, you are highly recommended to read the excellent and
    free `ProGit`_ book.

.. tip::

    If your IDE creates configuration files inside the project's directory,
    you can use global ``.gitignore`` file (for all projects) or
    ``.git/info/exclude`` file (per project) to ignore them. See
    `GitHub's documentation`_.

.. tip::

    Windows users: when installing Git, the installer will ask what to do with
    line endings, and suggests replacing all LF with CRLF. This is the wrong
    setting if you wish to contribute to Park-Manager! Selecting the as-is method
    is your best choice, as Git will convert your line feeds to the ones in the
    repository. If you have already installed Git, you can check the value of
    this setting by typing:

    .. code-block:: terminal

        $ git config core.autocrlf

    This will return either "false", "input" or "true"; "true" and "false" being
    the wrong values. Change it to "input" by typing:

    .. code-block:: terminal

        $ git config --global core.autocrlf input

    Replace ``--global`` by ``--local`` if you want to set it only for the active
    repository.

Get the Park-Manager Source Code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Get the Park-Manager source code:

* Create a `GitHub`_ account and sign in;

* Fork the `Park-Manager repository`_ (click on the "Fork" button);

* After the "forking action" has completed, clone your fork locally
  (this will create a ``park-manager`` directory):

.. code-block:: terminal

      $ git clone git@github.com:USERNAME/park-manager.git

* Add the upstream repository as a remote:

.. code-block:: terminal

      $ cd park-manager
      $ git remote add upstream git://github.com/park-manager/park-manager.git

* Adjust your branch to track the Park-Manager master remote branch, by
  default it'll track your origin remote's master:

.. code-block:: terminal

    $ git config branch.master.remote upstream

Check that the current Tests Pass
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    If you plan to only contribute documentation you may skip this step,
    and run ``make doc`` instead.

Now that Park-Manager is installed, check that all unit tests pass for your
environment:

.. code-block:: terminal

    $ make install # This uses Docker to install dependencies, but files are stored locally
    $ make test

If all went well you are now ready to start contributing, see also the
related sections about the projects coding standards and used conventions.

Step 2: Work on your Patch
--------------------------

The License
~~~~~~~~~~~

Before you start, you must know that all the patches you are going to submit
must be released under the *MPL-v2.0. license*, unless explicitly specified
in your commits.

Choose the right Branch
~~~~~~~~~~~~~~~~~~~~~~~

All patches must be targeted against the ``master`` branch,
including bug fixes and minor corrections like typo's.

Keep in mind that your changes will be cherry-picked to lower
branches by maintainers after the merge if they are applicable.

Create a Topic Branch
~~~~~~~~~~~~~~~~~~~~~

Each time you want to work on a patch for a bug or on an enhancement,
create a topic branch:

.. code-block:: terminal

    $ git checkout -b BRANCH_NAME upstream/master

.. tip::

    Use a descriptive name for your branch (like ``ticket_XXX`` where ``XXX``
    is the ticket number is a good convention for bug fixes).

The above checkout commands automatically switch the code to the newly created
branch (check the branch you are working on with ``git branch``).

Use your Branch in an Existing Project
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to test your code in an existing project that uses ``park-manager/park-manager``,
you can use the ``link`` utility provided in the Git repository you cloned previously.

This tool scans the ``vendor/`` directory of your project, finds Park-Manager packages
it uses, and replaces them by symbolic links to the ones in the Git repository.

.. code-block:: terminal

    $ php link /path/to/your/project

Before running the ``link`` command, be sure that the dependencies of the project you
want to debug are installed by running ``composer install`` inside it.

Work on your Patch
~~~~~~~~~~~~~~~~~~

Work on the code as much as you want and commit as much as you want; but keep
in mind the following:

* Follow the coding :doc:`standards <standards>` (use ``git diff --check``
  to check for trailing spaces -- also read the tip below);

* Add (unit) tests to prove that the bug is fixed or that the new feature
  actually works;

* Try hard to not break backward compatibility (if you must do so, try to
  provide a compatibility layer to support the old way) -- patches that break
  backward compatibility have less chance to be merged;

* Do atomic and logically separate commits (use the power of ``git rebase`` to
  have a clean and logical history);

* Squash irrelevant commits that are just about fixing coding standards
  or fixing typos in your own code;

* Never fix coding standards in some existing code as it makes the code review
  more difficult;

* In addition to this “code” pull request, you must also update the
  documentation when appropriate. See more in :doc:`contributing documentation </contributing/documentation/index>`
  section.

* Each patch defines one clear and agreed problem, and one clear, minimal,
  plausible solution. If done properly using Conventional Commits will
  automatically help you to make your commits atomic and clear;

* Write good commit messages (see the tip below).

.. tip::

    When submitting pull requests, Travis CI checks your code
    for common typos and verifies that you are using the :doc:`coding standards <standards>`
    as defined other chapters.

    A status is posted below the pull request description with a summary
    of any problems it detects or any build failures.

    A good commit message uses the `Conventional Commits <https://www.conventionalcommits.org/en/v1.0.0-beta.3/>`_
    guide lines, with the following additions:

    1. Separate subject from body with a blank line
    2. Limit the subject line to 50 characters
    3. Capitalize the subject line
    4. Do not end the subject line with a period
    5. Use the imperative mood (``add``/``fix`` not ``added``/``fixed``)
    6. Wrap the body at 72 characters
    7. Use the body to explain what and why vs. how

    For ``<type>`` use the following values:

        - build: Changes that affect the build system or external dependencies (example docker, webpack, travis)
        - ci: Changes to our CI configuration files and scripts (example scopes: Travis CI, BrowserStack, SauceLabs)
        - docs: Documentation only changes
        - feat: A new feature
        - fix: A bug fix
        - perf: A code change that improves performance
        - refactor: A code change that neither fixes a bug nor adds a feature
        - style: Changes that do not affect the meaning of the code (white-space, formatting, phpdoc comments, etc)
        - test: Adding missing tests or correcting existing tests

    For ``scope`` use the name of the Module (in lowercase), either:
    ``core``, ``webhosting``, ``customer``, ``domainreg``, etc.

Prepare your Patch for Submission
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When your patch is not about a bug fix (when you add a new feature or change
an existing one for instance), it must also include the following:

* An explanation of the changes in the relevant ``CHANGELOG`` file(s) (the
  ``[BC BREAK]`` or the ``[DEPRECATION]`` prefix must be used when relevant);

* An explanation on how to upgrade an existing application in the relevant
  ``UPGRADE`` file(s) if the changes break backward compatibility or if you
  deprecate something that will ultimately break backward compatibility.

* A ``BREAKING CHANGE:`` entry the commit message.

Step 3: Submit your Patch
-------------------------

Whenever you feel that your patch is ready for submission, follow the
following steps.

Rebase your Patch
~~~~~~~~~~~~~~~~~

Before submitting your patch, update your branch (needed if it takes you a
while to finish your changes):

.. code-block:: terminal

    $ git checkout BRANCH_NAME
    $ git rebase --pull upstream/master

When doing the ``rebase`` command, you might have to fix merge conflicts.
``git status`` will show you the *unmerged* files. Resolve all the conflicts,
then continue the rebase:

.. code-block:: terminal

    $ git add ... # add resolved files
    $ git rebase --continue

Check that all tests still pass and push your branch remotely:

.. code-block:: terminal

    $ git push --force origin BRANCH_NAME

.. _contributing-code-pull-request:

Make a Pull Request
~~~~~~~~~~~~~~~~~~~

You can now make a pull request on the ``park-manager/park-manager`` GitHub repository.

To ease the core team work, always include the modified components in your
pull request message, like in:

.. code-block:: text

    [Core] fix something
    [Webhosting] [Core add something

The default pull request description contains a table which you must fill in
with the appropriate answers. This ensures that contributions may be reviewed
without needless feedback loops and that your contributions can be included into
Park-Manager as quickly as possible.

Some answers to the questions trigger some more requirements:

* If you answer yes to "Bug fix?", check if the bug is already listed in the
  Park-Manager issues and reference it/them in "Fixed tickets";

* If you answer yes to "New feature?", you must update the documentation
  when appropriate;

* If you answer yes to "BC breaks?", the patch must contain updates to the
  relevant ``CHANGELOG`` and ``UPGRADE`` files;

* If you answer yes to "Deprecations?", the patch must contain updates to the
  relevant ``CHANGELOG`` and ``UPGRADE`` files;

* If you answer no to "Tests pass", you must add an item to a todo-list with
  the actions that must be done to fix the tests;

* If the "license" is not MPL-v2.0, just don't submit the pull request as it
  won't be accepted anyway.

If some of the previous requirements are not met, create a todo-list and add
relevant items:

.. code-block:: text

    - [ ] fix the tests as they have not been updated yet
    - [ ] submit changes to the documentation
    - [ ] document the BC breaks

If the code is not finished yet because you don't have time to finish it or
because you want early feedback on your work, add an item to todo-list:

.. code-block:: text

    - [ ] finish the code
    - [ ] gather feedback for my changes

As long as you have items in the todo-list, please prefix the pull request
title with "[WIP]".

In the pull request description, give as much details as possible about your
changes (don't hesitate to give code examples to illustrate your points). If
your pull request is about adding a new feature or modifying an existing one,
explain the rationale for the changes. The pull request description helps the
code review and it serves as a reference when the code is merged (the pull
request description and all its associated comments are part of the merge
commit message).

Rework your Patch
~~~~~~~~~~~~~~~~~

Based on the feedback on the pull request, you might need to rework your
patch. Before re-submitting the patch, rebase with ``upstream/master`` or
the branch your pull request is targeting, don't merge; and force the push
to the origin:

.. code-block:: terminal

    $ git rebase -f upstream/master
    $ git push --force-with-lease origin BRANCH_NAME

.. note::

    When doing a ``push --force-with-lease``, always specify the branch name explicitly
    to avoid messing other branches in the repo (``--force-with-lease`` tells Git that
    you really want to mess with things so do it carefully).

Often, Park-Manager team members will ask you to "squash" your commits.
This means you will convert many commits to one commit.

To do this, use the rebase command:

.. code-block:: terminal

    $ git rebase -i upstream/master
    $ git push --force-with-lease origin BRANCH_NAME

After you type this command, an editor will popup showing a list of commits:

.. code-block:: text

    pick 1a31be6 first commit
    pick 7fc64b4 second commit
    pick 7d33018 third commit

To squash all commits into the first one, remove the word ``pick`` before the
second and the last commits, and replace it by the word ``squash`` or just
``s``. When you save, Git will start rebasing, and if successful, will ask
you to edit the commit message, which by default is a listing of the commit
messages of all the commits. When you are finished, execute the push command.

.. _ProGit: http://git-scm.com/book
.. _GitHub: https://github.com/join
.. _`GitHub's Documentation`: https://help.github.com/articles/ignoring-files
.. _`Park-Manager repository`: https://github.com/park-manager/park-Manager
