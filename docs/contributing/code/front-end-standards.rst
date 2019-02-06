Front-end Coding Standards
==========================

Twig Templates
--------------

Here's a short example containing most features described below:

.. code-block:: jinja

    {% short_method_call_that_fits_on_one_line(arguments) %}

    {% link_to(
      some_object_with_a_long_name.title,
      parent_object_child_object_path(some_object_with_a_long_name)
    ) %}

* Follow the `Twig Coding Standards`_;

* Add Form buttons in the templates, not in the form classes or the action;

* Don't perform complex logic in the template; use an extension,
  or better compute the value as part of the ViewModel.

* When wrapping long lines, keep the method name on the same line of the
  interpolation operator ``{%`` and keep each method argument on its own line;

* Do not add a license block in Twig template, all templates are provided under
  the same license as the project itself (MPL v. 2.0.);

.. _`Twig Coding Standards`: http://twig.sensiolabs.org/doc/coding_standards.html
