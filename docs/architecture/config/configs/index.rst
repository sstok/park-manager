SystemConfig Documentation
==========================

The System ServiceBus works outside of the Application itself and therefor,
the service configurations need to be set-up properly in-advance.

This chapter explains the process for managing SystemConfig set-ups.
All configuration set-ups provided by the Park-Manager project are expected
to follow this process.

1. Plan or Describe
-------------------

First start with a proper plan or requirements list for what the configuration
must support and allows in terms of managing by Park-Manager.

.. note::

    If a set-up doesn't provide enough flexibility the systems administrator
    might need to apply some manual changes, which could destabilize the
    systems configuration. Which is why good documentation is critical!

2. Implement and Test
---------------------

Implement the planned configuration set-up and test if it works properly
and follows the original specification.

3. Document
-----------

Document how the system is set-up, which configurations must remain intact
for Park-Manager to function properly and what can be safely changed.

Also explain why something was done in a specific way if this is not directly
clear or diverges from the "recommended" configuration of the vendor.

Explain pros and cons of the chosen implementation.

Also document how the tests were performed, and which are attention points
when performing the tests.

.. tip::

    If possible an automated script also suffices for a testing documentation.
