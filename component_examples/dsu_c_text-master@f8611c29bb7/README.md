CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Functionality
 * Troubleshooting
 * Maintainers
 * Extend

INTRODUCTION
------------

The _Content: Text_ component can be used to display a block of text.


REQUIREMENTS
------------

This module requires the following modules:

* Link attributes (https://www.drupal.org/project/link_attributes)

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Add a new field of type _Paragraph_ to the content type you want to use the
_Content: Text_ component on.

* Select _Content: Text_ in the list of allowed paragraph types for this field
and save the field settings.

FUNCTIONALITY
-------------

* The _Content: Text_ component provides the following fields:
   - **Text Body**: The text to be displayed in the component.
   - **Title**: The title of the component (optional).
   - **Subtitle**: The subtitle of the component (optional).
   - **CTA**: The CTA button of the component (optional).

* The _Advanced_ collapsible field allows customizing the text color as well as
the text position within it's container

TROUBLESHOOTING
---------------

* If the content does not display correctly, make sure the following template
files are not overriden in your theme's templates directory or a custom module.
If you have overriden these template files, make sure their contents are up to
date with the latest version of the module.

   - `paragraph--c-text.html.twig`

EXTEND
------

 * You can implement `hook_theme()` in order to extend the default template of
 the paragraph.
 * You can implement `hook_preprocess_paragraph()` to alter the data before it
 is passed to the template file.
 * You can override the default CSS library in your custom theme in order to
 change the styling of the component.


MAINTAINERS
-----------

* Nestle Webcms team.