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

The _Content: Image_ component can be used to create an image with title,
subtitle, description and CTA.


REQUIREMENTS
------------

This module requires the following contrib modules:

* Link Attributes(https://www.drupal.org/project/link_attributes)


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Add a new field of type _Paragraph_ to the content type you want to use the
_Content: Image_ component on.

* Select _Content: Image_ in the list of allowed paragraph types for this field
and save the field settings.

FUNCTIONALITY
-------------

* The _Content: Image_ component provides the following fields:
  - **Image**: The main image.
  - **Title**: The title of the image.
  - **Subtitle**: The subtitle of the image.
  - **Body**: The description of the image.
  - **CTA**: The call to action of the image.

* The title, subtitle, body and CTA fields are displayed on top of the image.
* The _Advanced_ collapsible field allows customizing the text and background
color as well as the text position within the image


TROUBLESHOOTING
---------------

* If the content does not display correctly, make sure the following template
files are not overriden in your theme's templates directory or a custom module.
If you have overriden these template files, make sure their contents are up to
date with the latest version of the module.

   - `paragraph--c-image.html.twig`

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