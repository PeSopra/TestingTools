<?php

namespace Drupal\dsu_ratings_reviews\Plugin\DsField\Comment;

use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the subject comment
 *
 * @DsField(
 *   id = "dsu_ratins_reviews_subject",
 *   title = @Translation("Subject"),
 *   provider = "dsu_ratings_reviews",
 *   entity_type = "comment",
 *   ui_limit = {"dsu_ratings_reviews_comment_type|*"},
 * )
 */

class Subject extends Title {

  public function build() {
    $comment = $this->entity();
    $build = parent::build();
    if(isset($build['#context']['entity_url'])){
      $build['#context']['entity_url'] = $comment->in_preview ? Url::fromRoute('<front>') : $comment->permalink();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityRenderKey() {
    return 'subject';
  }

}
