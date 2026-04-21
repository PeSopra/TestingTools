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

The _Content: Teaser Cycle_ component can be used to display images with
optional text and CTA buttons in a carousel.


REQUIREMENTS
------------

This module requires the following modules:

* Options Table (https://www.drupal.org/project/options_table)

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Add a new field of type _Paragraph_ to the content type you want to use the
_Content: Teaser Cycle_ component on.

* Select _Content: Teaser Cycle_ in the list of allowed paragraph types for this
field and save the field settings.

FUNCTIONALITY
-------------

* The _Content: Teaser Cycle_ component provides the following fields:
  - **Cycle Item**: The teaser cycle items to be displayed in the component. As
  many items as are needed can be added.
  - **Display Options:**: The teaser fields that should be displayed for each
  item.

* Each _Content: Teaser Cycle Item_ provides the following fields:
   - **Image**: The image of the teaser.
   - **Product Title**: The title of the teaser.
   - **Title Style**: The heading style of the teaser title.
   - **Short Description**: The description of the teaser.
   - **CTA Button**: The URL and text of the CTA button.

TROUBLESHOOTING
---------------

* If the content does not display correctly, make sure the following template
files are not overriden in your theme's templates directory or a custom module.
If you have overriden these template files, make sure their contents are up to
date with the latest version of the module.

   - `paragraph--c-teasercycle.html.twig`
   - `paragraph--c-teasercycle-item.html.twig`

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