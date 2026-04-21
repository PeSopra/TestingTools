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

The _Content: Card Grid_ component can be used to display a grid of cards with
an image and optional text and CTA buttons. The grid can be displayed in
different view modes.


REQUIREMENTS
------------

This module requires the following modules:

* Link Attributes (https://www.drupal.org/project/link_attributes)

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Add a new field of type _Paragraph_ to the content type you want to use the
_Content: Card Grid_ component on.

* Select _Content: Card Grid_ in the list of allowed paragraph types for this
field and save the field settings.

FUNCTIONALITY
-------------

* The _Content: Card Grid_ component provides the following fields:
   - **View Mode**: The view mode to use for the cards in the grid.
   - **Optional fields**:
      - **Title**: The title of the grid.
   - **Subitems**: The card items to be displayed in the grid. As many cards as
   are needed can be added.

* Each _Content: Card Grid Item_ provides the following fields:
   - **Title**: The title of the card.
   - **Body**: The body of the card.
   - **Image**: The image of the card.
   - **Optional fields**:
      - **Subtitle**: The subtitle of the card.
      - **CTA**: The URL and text of the CTA button.

TROUBLESHOOTING
---------------
* If the content does not display correctly, make sure the following template
files are not overriden in your theme's templates directory or a custom module.
If you have overriden these template files, make sure their contents are up to
date with the latest version of the module.

   - `paragraph--ln-c-cardgrid.html.twig`
   - `paragraph--ln-c-grid-card-item.html.twig`

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
