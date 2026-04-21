CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Functionality
 * Troubleshooting
 * Maintainers
 * Extend

INTRODUCTION
------------

The Lightnest Accordion can be used to create a vertically stacked list of items
of any paragraph content.

Each item can be "expanded" or "collapsed" to reveal the content associated with
that item.

INSTALLATION
------------

*  Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

* Add a new field of type _Paragraph_ to the content type you want to use the
accordion on.
* Select _Content: Accordion_ in the list of allowed paragraph types for this
field and save the field settings.

FUNCTIONALITY
-------------

* The accordion component allows creating as many accordion items as needed.
* Each accordion item has a main text field and a teaser text field.

TROUBLESHOOTING
---------------

 * If the content does not display correctly, make sure the following template
 files are not overriden in your theme's templates directory or a custom module.
 If you have overriden these template files, make sure their contents are up to
 date with the latest version of the module.

   - `paragraph--accordion.html.twig`
   - `paragraph--accordion-item.html.twig`

EXTEND
------

 * You can implement `hook_theme()` in order to extend the default template of
 Lightnest paragraph Accordion.
 * You can override the default CSS library in your custom theme in order to
 change the styling of the accordion component.

MAINTAINERS
-----------

* Nestle Webcms team.