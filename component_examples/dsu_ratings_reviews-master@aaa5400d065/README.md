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

This modules provides a Ratings & Reviews functionality for any content type and
a dashboard to calculate KPIs and metrics about all reviews. It allows rating
nodes from 1 to 5 by means of a star rating widget as well as adding comments to
nodes, which can be flagged as useful or not useful.

REQUIREMENTS
------------

This module requires the following modules:

* Display Suite (https://www.drupal.org/project/ds)
* Field Group (https://www.drupal.org/project/field_group)
* Flag (https://www.drupal.org/project/flag)
* Voting API (https://www.drupal.org/project/votingapi)

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

1. Visit the _Manage fields_ tab of the entity bundle where you want to enable
   the _Ratings & Reviews_ functionality.

2. Add a new field of type _Comments_ and select _DSU Ratings and Reviews_ as
the comment type. Set the default value of the field to _Open_. Configure the
rest of settings according to your needs and save the field.

3. Go to the _Manage display_ tab and choose the field formatter
_DSU Ratings and Reviews Comment list_ for your newly created comments field.

4. This module will automatically set permissions for anonymous and
authenticated users. By default, both roles will be able to see comments and
flag them as useful or not useful. Only authenticated users will be able to
post comments. You may customize these permissions as you wish or extend them
to other roles.

  * The necessary permissions to see and flage comments are these:
    - `access comments`
    - `access content`
    - `flag dsu_ratings_comment_unuseful`
    - `flag dsu_ratings_comment_useful`
    - `unflag dsu_ratings_comment_unuseful`
    - `unflag dsu_ratings_comment_useful`
    - `view media`

  * The permissions needed to add comments are, in addition to the former ones:
    - `post comments`
    - `create dsu_comment_image media`

5. Go to `/admin/config/lightnest/dsu_ratings_reviews/settings` and configure
the following settings:
    - **Terms and Conditions**: The text to show to the user before commenting.
    - **Marketing Opt-In**: Whether users who leave reviews should be given the
    option to opt-in to marketing emails.
    - **Moderation Email Subject**: Subject to be used to notify administrators
    about the new review.
    - **Moderation Email Body**: Body to be used to notify administrators about
    the new review.

6. To remove the _not verified_ text from the anonymous username, go
   to `/admin/appearance/settings` and uncheck option _User verification status
   in comments_ under _Page Element display_.

FUNCTIONALITY
-------------

* A "Write a review" form is displayed in content types where commenting is
  enabled when the user has permission to post comments. This form allows users
  to rate or flag the content and add a comment.
* Only the moderator user will be able to reply to user comments.
* Ratings and reviews can be filtered by rating and flagged as useful or not.
* Ratings and reviews can be sorted by usefulness.
* A dashboard is provided to calculate KPIs and metrics about all reviews for
  each content item. Go
  to `/admin/config/lightnest/dsu_ratings_reviews/dashboard`.

TROUBLESHOOTING
---------------

* If you don't see any filters or the "Write a review" text, make sure
 you selected the right formatter on the installation steps to
 replace "Comment" field formatter.
* If comments do not show for other users, go to People > Permissions and
  enable 'View comments'. You may also want to configure permissions
  'Post comments' and 'Edit own comment' for common users.
* If brand user cannot reply to comments, go to People > Permissions
  and enable 'reply rating comments' permission for brand roles.
* If the colorbox popup doesn't show up when clicking on images, make sure
  "colorbox" library is installed in the /libraries/ folder, and clean caches.

MAINTAINERS
-----------

* Nestle Webcms team.
