<?php

namespace Drupal\dsu_ratings_reviews\Plugin\DsField\Comment;

use Drupal\Core\Link;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the changed time comment
 *
 * @DsField(
 *   id = "dsu_ratins_reviews_parent_data",
 *   title = @Translation("Parent data"),
 *   provider = "dsu_ratings_reviews",
 *   entity_type = "comment",
 *   ui_limit = {"dsu_ratings_reviews_comment_type|*"},
 * )
 */

class ParentData extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity();
    $build = [];
    if($comment_parent = $comment->getParentComment()){
      $permalink_uri_parent = $comment_parent->permalink();
      $attributes = $permalink_uri_parent->getOption('attributes') ?: [];
      $attributes += ['class' => ['permalink'], 'rel' => 'bookmark'];
      $permalink_uri_parent->setOption('attributes', $attributes);
      $parent_title = Link::fromTextAndUrl($comment_parent->getSubject(), $permalink_uri_parent)->toString();
      $account_parent = $comment_parent->getOwner();
      $username = [
        '#theme' => 'username',
        '#account' => $account_parent,
      ];
      $parent_author = \Drupal::service('renderer')->render($username);
      $parent = $this->t('In reply to @parent_title by @parent_username',
        ['@parent_username' => $parent_author, '@parent_title' => $parent_title]);
      $build = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'class' => ['sr-only', 'visually-hidden'],
        ],
        '#value' => $parent
      ];
    }
    return $build;
  }

}
