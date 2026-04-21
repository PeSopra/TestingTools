<?php

namespace Drupal\dsu_ratings_reviews\Plugin\DsField\Comment;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\ln_srh_basic\Plugin\DsField\CountBase;
use Drupal\ln_srh_basic\SRHBasicConstants;

/**
 * Plugin that renders the changed time comment
 *
 * @DsField(
 *   id = "dsu_ratins_reviews_new_indicator_timestamp",
 *   title = @Translation("New Indicator Timestamp"),
 *   provider = "dsu_ratings_reviews",
 *   entity_type = "comment",
 *   ui_limit = {"dsu_ratings_reviews_comment_type|*"},
 * )
 */

class NewIndicatorTimestamp extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity();
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['hidden', 'text-danger'],
        'data-comment-timestamp' => $comment->getChangedTime(),
      ],
    ];
    return $build;
  }

}
