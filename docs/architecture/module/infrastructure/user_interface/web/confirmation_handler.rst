ConfirmationHandler
===================

The ConfirmationHandler provides a safety boundary to confirm an action.

Each handler types are care of proper submission with CSRF tokens
and handing content negotiation for ridge-client applications.

The ConfirmationHandler comes comes in two favors: the default confirmation
handler. And a checked confirmation handler that asks the user to provide the
"name" of the selected item, which is primarily usable for a delete operation.

Default confirmation handler
----------------------------

The "default" ``ConfirmationHandler`` accepts a confirmation using a single
button, and shows some basic information like the selected item::

    use ParkManager\Component\ConfirmationHandler\ConfirmationHandler;

    $confirmationHandler = new ConfirmationHandler($twigEnvironment, /* CsrfTokenManagerInterface */ $csrfTokenManager);

    // First configure the handlers UI details. This step is mandatory, and should contain information about the current
    // operation and selected item.
    $confirmationHandler->configure('title', 'message', 'yes button-label');

    // Optionally set a target URL for the cancel button. In client-side only version this is ignored.
    // Alternative you can use a route-name if the used template "accepts" this.
    $confirmationHandler->setCancelUrl('/list');

    // Handle the request to determine if the action was confirmed.
    // The second argument contains an optional array of Request attribute names (route arguments)
    // that are used for the CSRF token computation: handleRequest($request, ['id', 'account'])
    $confirmationHandler->handleRequest($request);

    if ($confirmationHandler->isConfirmed()) {
        // If the action is indeed confirmed, perform the operation
        // and redirect the user back to the overview.

        // Make sure to return or exit here! Otherwise the confirmation is shown again.
    }

    return $confirmationHandler->render(
        '@ParkManagerCoreModule/confirmation.html.twig',
        ['additional-variables-passed-to-the-template']
    );

The ``CheckedConfirmationHandler`` asks the user to provide the name of
the item before confirmation is accepted. Usage is the same as shown above,
but ``configure()`` requires a value to-match-against is provided::

    $confirmationHandler = new CheckedConfirmationHandler($twigEnvironment, /* CsrfTokenManagerInterface */ $csrfTokenManager);
    $confirmationHandler->configure('title', 'message (with %value% placeholder)', 'required-value', 'yes button-label');
    $confirmationHandler->setCancelUrl();

    $confirmationHandler->handleRequest($request, 'id-attribute-name: id');

    if ($confirmationHandler->isConfirmed()) {
        // If the action is indeed confirmed, perform the operation
        // and redirect the user back to the overview.
    }

    return $confirmationHandler->render('@CoreWeb/delete_confirmation.html.twig', ['additional-variables-passed-to-the-template']);

To is mainly used for dangerous actions like deletion or transferring ownership.
Asking to type the name ensures the user selected the correct item, and are fully
aware they selected the correct item.

Content Negotiation
-------------------

A ConfirmationHandler can used for both server-side rendered pages, and
ridge client application (JavaScript). The Handler automatically detects
which response needs to be provided.

* The template is ignored when only JSON is Accepted by the client;

* Use a ``PUT``/``DELETE`` with a token (and purpose) provided in the
  HTTP header, eg. ``X-CSRF: token; purpose``;

* Returns a ``hydra:error`` context response in case of an input error
  (invalid token, provided name does not equal the expected value).

.. tip::

    When using the confirmation handler with a Data list be sure to apply
    the following conventions:

    * Use the list name (eg. users, webhosting-accounts) as the purpose
      of the token;

    * Remove the row when delete is used (to prevent ghost records).
