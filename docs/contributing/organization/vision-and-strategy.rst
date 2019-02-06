Vision & Strategy
=================

Vision & strategy is defined by the Project Leader, Core Team and Community members.

If you would like to suggest a new tool, process, feel free to submit a PR to this
section of the documentation.

Be sure to motivate your suggestion.

.. note::

    Feel free to use external articles to motivate your proposal,
    however don't simple post website link(s) with no additional information.

    Suggestions with no helpful information and no feedback are automatically
    closed after a reasonable period of time.

GitHub
------

We use GitHub as the main tool to organize the community work and everything that
happens around Park-Manager. Releases, bug reports, feature requests, roadmap
management, etc. happens on this platform.

If you are not sure about your issue, please use the Park-Manager Slack to discuss
it with the fellow community members before opening it on GitHub.

Milestones
~~~~~~~~~~

We use milestones to mark the lowest branch an issue or a PR applies to.

For example, if a bug report is marked with 1.0 milestone, the related bugfix
PR should be opened against 1.0 branch. Then, after this PR is merged,
it would be released in the next 1.0.x release.

Learn more about the :doc:`/contributing/organization/release-process`.

.. _pull-request-checklist:

Pull Request Checklist
~~~~~~~~~~~~~~~~~~~~~~

Before any PR is merged, the following things need to be confirmed:

#. Changes can be included in the upcoming release.
#. PR has been approved by at least 1 fellow Core Team member.
#. PR adheres to the PR template and contains the MPL v. 2.0 license.
#. PR includes relevant documentation updates.
#. PR contains appropriate UPGRADE file updates if necessary.
#. PR is properly labeled and milestone is assigned if needed.
#. All required checks are passing. It is green!

Certain PRs can only be merged by the Project Lead:

* BC Breaks;
* Introducing new modules or high level architecture layers;
* Renaming existing components;
* If in doubt, ask your friendly neighborhood Project Lead;

A PR must only be merged using `Hubkit`_, Hubkit ensures Changelog
generation works as expected and keeps all relevant information
bundled with the Git merge-commit itself.

Voting on a Proposed Feature
----------------------------

Anyone is free to vote on a proposed feature.

.. tip::

    Don't work on implementing a feature request or idea until
    there enough positive votes, rather focus on features that
    are accepted and can be implemented now.

Voting on a feature request is done by either giving a review approval
for a pull request or giving a ``Thumb up`` emoji-reaction (not a reply).

A negative vote on the suggestion must always have a clear (and respectful)
explanation why you think this is not a good idea.

Don't give a "Thumb down" emoji-reaction when you like the idea but have some
concerns about the issue.

.. note::

    While everyone is free to vote on a feature request, the proposed
    feature must fit within the vision of the Park-Manager project.

    If at least 30% of the Core Team gives a negative vote, the feature
    request is rejected. You cannot vote your own proposal.

.. _`Hubkit`: http://www.park-manager.com/hubkit/
