Contributing Translations
=========================

* Always use identification keys for translations instead of content strings;

* Translations keys should always describe their purpose and not their location.

  A transformation for a form label "Username", would be named ``label.username``,
  not ``edit_form.label.username``;

  An error message should give a good indication of the reason, ``password_not_strong_enough``
  not ``password_invalid``;

* Follow the `Translation Best practices`_;

* Reuse translation keys when possible, use only section specif keys when
  they depend on context, like ``profile.edit.title``;

* Use Intl translator format for better flexibility;

* Keep translations per logical domain:

    * ``validations``: holds the translation for validator violation messages;
    * ``navigation``: holds the translations for navigation items, like the
      navigation menu and breadcrumbs;
    * ``search``: holds the translations for search-field labels;
    * ``messages``: holds translations with no specific domain;

.. _`Translation Best practices`: http://php-translation.readthedocs.io/en/latest/best-practice/index.html
