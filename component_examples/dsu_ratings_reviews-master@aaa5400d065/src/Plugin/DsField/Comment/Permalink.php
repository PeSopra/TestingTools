<?php

namespace Drupal\dsu_ratings_reviews\Plugin\DsField\Comment;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\Date;

/**
 * Plugin that renders the changed time comment
 *
 * @DsField(
 *   id = "dsu_ratins_reviews_permalink",
 *   title = @Translation("Permalink"),
 *   provider = "dsu_ratings_reviews",
 *   entity_type = "comment",
 *   ui_limit = {"dsu_ratings_reviews_comment_type|*"},
 * )
 */

class Permalink extends Date {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity();
    $field = $this->getFieldConfiguration();
    $date_format = str_replace('ds_post_date_', '', $field['formatter']);
    if ($comment->in_preview) {
      $permalink = Link::fromTextAndUrl(t('Permalink'), Url::fromRoute('<front>'))->toString();
    }
    else {
      $permalink = Link::fromTextAndUrl(t('Permalink'), $comment->permalink())->toString();
    }
    $build = [
      'comment__time' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['comment__time']
        ],
        '#value' => $this->dateFormatter->format($comment->getCreatedTime(), $date_format),
      ],
      'comment__permalink' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['comment__time']
        ],
        '#value' => $permalink,
      ],
    ];
    return $build;
  }

}
