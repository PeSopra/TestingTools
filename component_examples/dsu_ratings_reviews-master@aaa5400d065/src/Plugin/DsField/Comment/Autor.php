<?php

namespace Drupal\dsu_ratings_reviews\Plugin\DsField\Comment;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the comment author
 *
 * @DsField(
 *   id = "dsu_ratins_reviews_author",
 *   title = @Translation("Author"),
 *   provider = "dsu_ratings_reviews",
 *   entity_type = "comment",
 *   ui_limit = {"dsu_ratings_reviews_comment_type|*"},
 * )
 */

class Autor extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity();
    $account = $comment->getOwner();
    $build = [
      '#theme' => 'username',
      '#account' => $account,
    ];
    return $build;
  }

}
