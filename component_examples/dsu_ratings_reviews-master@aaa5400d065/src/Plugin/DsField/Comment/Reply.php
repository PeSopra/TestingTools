<?php

namespace Drupal\dsu_ratings_reviews\Plugin\DsField\Comment;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\CommentStorageInterface;

/**
 * Plugin that renders the comment reply
 *
 * @DsField(
 *   id = "dsu_ratins_reviews_reply",
 *   title = @Translation("Reply"),
 *   provider = "dsu_ratings_reviews",
 *   entity_type = "comment",
 *   ui_limit = {"dsu_ratings_reviews_comment_type|*"},
 * )
 */

class Reply extends DsFieldBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var CommentInterface $comment */
    $comment = $this->entity();
    /** @var CommentStorageInterface $comment_storage */
    $comment_storage = $this->entityTypeManager->getStorage('comment');
    if($child_ids = $comment_storage->getChildCids([$comment->id() => $comment])){
      $view_builder = $this->entityTypeManager->getViewBuilder('comment');
      return $view_builder->viewMultiple($comment_storage->loadMultiple($child_ids), DsuRatingsReviewsConstants::REPLY_VIEW_MODE);
    }
    return [];
  }

}
