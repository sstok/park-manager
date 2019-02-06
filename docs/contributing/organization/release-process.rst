The Release Process
===================

This document explains the process followed by the Park-Manager project to develop,
release and maintain its different versions.

.. note::

    Until a stable v1.0.0 version is released this document is not
    in-effect yet. Any BC breakage results in a new minor version release
    and directly fully discontinues all previous versions (no patch releases
    are provided for older versions).

Park-Manager releases follow the `semantic versioning`_ strategy and they are
published through a *time-based model*:

* A new Park-Manager minor version (for example 1.1, 1.2, etc.) comes out every *month*
* A new Park-Manager major version (for example 2.0, 3.0) comes out every *two years*
  (around the same time Symfony releases a new major version) and it's
  released at the same time as the last minor version of the previous
  major version;
* A new Park-Manager patch version (for example 1.24.1, 1.24.2, etc.) comes out
  every *month*, only for the last maintained minor version;

Development
-----------

The full development period for any minor version lasts one month, unless
there to many critical issues. When there are to many critical issues with
a new feature the release might be delayed with two a weeks maximum, or
the offending feature might be reverted until the next scheduled release.

During the development period, any new feature can be reverted if it won't be
finished in time or if it won't be stable enough to be included in the
coming release.

Maintenance
-----------

Each Park-Manager major version is maintained for a fixed period of time.
This maintenance is divided into:

* *Bug fixes and security fixes*: During this period, being *three months* long,
  all issues can be fixed. The end of this period is referenced as being the
  *end of maintenance* of a release.

* *Security fixes only*: During this period, being *six months* long,
  only security related issues can be fixed. The end of this period is referenced
  as being the *end of life* of a release.

* A minor version is maintained until the release of the next minor version,
  the release of version 1.2 directly discontinues maintenance of version 1.1;

Backward Compatibility
----------------------

All Park-Manager releases have to comply with our :doc:`Backward Compatibility Promise </contributing/code/bc>`.

Whenever keeping backward compatibility is not possible, the feature, the
enhancement or the bug fix will be scheduled for the next major version.

Rationale
---------

This release process was adopted to give more *predictability* and
*transparency*. It was inspired based on the following goals:

* Shorten the release cycle (allow users to benefit from the new
  features faster);
* Improve the experience of Park-Manager core contributors: everyone knows when a
  feature might be available in Park-Manager;
* Give companies a strict and predictable timeline they can rely on to plan
  their upgrades.

The one month period was chosen to allow features to be shipped almost
instantly, while gathering early feedback from experimental features.

It also allows for plenty of time to work on new features and it allows for non-ready
features to be postponed to the next version without having to wait too long
for the next cycle.

.. _Semantic Versioning: http://semver.org/
