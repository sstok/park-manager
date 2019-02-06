Park-Manager System
===================

Park-Manager is build-up from multiple modules, including the CoreModule
that forms the basis for booting the Application.

In this chapter you can find more information about the system architecture,
and how to work with the :doc:`SystemConfig </architecture/config/index>`.

.. note::

    Park-Manager is build on the `Symfony Framework`_, this chapter assumes
    you have advanced experience with this framework.

    The architecture follows a Domain Driven Design approach, some basic
    knowledge is required. If you are completely new to this, checkout
    :doc:`Introduction to DDD </architecture/ddd_introduction>` first.

Topics
------

* The :doc:`/architecture/modules` chapter explains in great detail how the Modules
  are constructed, integrated, interact and how to achieve a strict separation
  of concerns.

* The :doc:`runtime_system` is responsible for booting the application
  and background services.

* The :doc:`SystemConfig </architecture/config/index>` gives a detailed overview
  and explanation of communicating with external system configurations like
  Email storage, FTP, and web-server virtual-hosts.

* The :doc:`/architecture/user_system` explains how to work with the different
  user types (Client and Administrator, Reseller) and how to link ownership
  of a "product".

* The application is separated into tree :doc:`/architecture/sections`: Client,
  Administrator, and ResellerSpace 0 that gives Administrators a private
  section for company brand websites.

  In chapter you can find more information about the routing schema's,
  access rules separation.

.. toctree::
    :hidden:

    modules/index
    config/index
    user_system
    sections

.. _`Symfony Framework`: http://symfony.com
