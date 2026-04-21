<?php

namespace Drupal\dsu_ratings_reviews\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RatingsReviewsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('comment.reply')) {
      $route->setRequirement('_custom_access', '\Drupal\dsu_ratings_reviews\Controller\RatingsReviewsCommentController::replyFormAccess');
    }
    if ($route = $collection->get('flag.action_link_flag')) {
      $route->setDefault('_controller', '\Drupal\dsu_ratings_reviews\Controller\RatingsReviewsActionLinkController::flag');
    }

  }

}
