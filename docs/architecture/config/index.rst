SystemConfig
============

The SystemConfig Component manages the configuration for external systems
including:

* :doc:`Managing OS system users </core/system_config/system_users>`;
* :doc:`Mail storage (and content filtering) </core/system_config/mail>`;
* :doc:`FTP Users and IP-address access listing </core/system_config/ftp>`;
* :doc:`Web server virtual-hosts configuration (including TLS) </core/system_config/virtual_hosts>`;
* :doc:`Cron jobs and scheduled tasks </core/system_config/cron>`.

.. note::

    The SystemConfig is a separate boundary, communication with this system
    is handled through a ServiceBus. A Module cannot directly access the
    information.

    "Ownership" of an entity is managed by the communicating Modules,
    the Module uses a ``RootEntityOwner`` object with the (uuid) id of
    either an Webhosting Account or System Administrator.

    *The SystemConfig Component doesn't enforce it's own access control
    as this is handled by a ServiceBus MessageGuard.*

.. toctree::
    :hidden:

    system_users
    mail
    ftp
    virtual_hosts
    cron

.. _system-service-bus:

System ServiceBus
-----------------

Access to protected system services is handled using a dedicated ServiceBus
operating outside the application. Access is heavily guarded and operations
are explicit.

TBD.

Documentation Management
~~~~~~~~~~~~~~~~~~~~~~~~

The System ServiceBus works outside of the Application itself and therefor,
the service configurations need to be set-up properly in-advance.

While a source-code is a good reference for understanding how an application
or library works, a system set-up however is more complex and can be applied
in various forms.

To allow full customizing, all service set-ups have a fully documented
implementation schema (called a Set-up Schema).

In a Set-up Schema you can find all details regarding a chosen set-up,
including the motivation for this chosen set-up, how to test the set-up
(after changing) and what things you can and can not change.

.. toctree::
    :maxdepth: 1

    configs/index
