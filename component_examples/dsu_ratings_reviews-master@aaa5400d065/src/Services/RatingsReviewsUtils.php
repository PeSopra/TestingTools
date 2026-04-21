<?php

namespace Drupal\dsu_ratings_reviews\Services;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * Class RatingsReviewsService.
 */
class RatingsReviewsUtils {

  /**
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager){
    $this->entityTypeManager = $entityTypeManager;
    $this->commentStorage = $entityTypeManager->getStorage('comment');
  }

  /**
   * Get comments ids from Entity
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $root
   * @param $status
   *
   * @return array|int
   */
  public function getCommentsIdsByEntity(ContentEntityInterface $entity, $status = 1, $root = TRUE){
    $query = $this->commentStorage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('comment_type', DsuRatingsReviewsConstants::COMMENT_TYPE)
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id',$entity->id());
      if($status !== NULL){
        $query->condition('status', $status);
      }
    if($root){
      $query->notExists('pid');
    }
    $result = $query->execute();
    return !empty($result) ? $result : [];
  }

  /**
   * Get recommended comments ids from entity
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $status
   *
   * @return array|int
   */
  public function getRecommendedCommentsIdsByEntity(ContentEntityInterface $entity, $status = 1, $root = TRUE) {
    $query = $this->commentStorage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('comment_type', DsuRatingsReviewsConstants::COMMENT_TYPE)
      ->condition(DsuRatingsReviewsConstants::RECOMMEND_FIELD, 1);
    if ($status !== NULL) {
      $query->condition('status', $status);
    }
    if ($root) {
      $query->notExists('pid');
    }
    $result = $query->execute();

    return !empty($result) ? $result : [];
  }

  /**
   * Get recommended comments ids from entity type
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $status
   *
   * @return array|int
   */
  public function getRecommendedCommentsIdsByEntityType($entityTypeId, $bundle = NULL, $status = 1, $root = TRUE) {
    $entityIds = $this->getEntitiesIdsByEntityType($entityTypeId,$bundle);
    $query = $this->commentStorage
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_type', $entityTypeId)
      ->condition('entity_id', $entityIds, 'IN')
      ->condition('comment_type', DsuRatingsReviewsConstants::COMMENT_TYPE)
      ->condition(DsuRatingsReviewsConstants::RECOMMEND_FIELD, 1);
    if ($status !== NULL) {
      $query->condition('status', $status);
    }
    if ($root) {
      $query->notExists('pid');
    }
    $result = $query->execute();

    return !empty($result) ? $result : [];
  }

  public function getCommentsIdsByEntityType($entityTypeId, $bundle = NULL, $status = 1, $root = TRUE){
    $entityIds = $this->getEntitiesIdsByEntityType($entityTypeId,$bundle);
    // Get all comments from entity ids
    $commentsIds = [];
    if(!empty($entityIds)){
      $query = $this->commentStorage
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('comment_type', DsuRatingsReviewsConstants::COMMENT_TYPE)
        ->condition('entity_type', $entityTypeId)
        ->condition('entity_id', $entityIds, 'IN');
        if($status !== NULL){
          $query->condition('status', $status);
        }
      if($root){
        $query->notExists('pid');
      }
      $commentsIds = $query->execute();
    }

    return !empty($commentsIds) ? $commentsIds : [];
  }

  public function getEntityTypeVotingsResults($entity_type_id, $bundle) {
    $results = [];
    if($commentsIds = $this->getCommentsIdsByEntityType($entity_type_id,$bundle)){
      $results = $this->getCommentsVotingsResults($commentsIds);
    }

    return $results;
  }

  public function getEntityVotingsResults(ContentEntityInterface $entity) {
    $results = [];
    if($commentsIds = $this->getCommentsIdsByEntity($entity)){
      $results = $this->getCommentsVotingsResults($commentsIds);
    }

    return $results;
  }

  public function getCommentsVotingsResults($commentsIds){
    $results = [];
    $comments = $this->entityTypeManager->getStorage('comment')->loadMultiple($commentsIds);
    foreach ($comments as $comment){
      if($comment->hasField(DsuRatingsReviewsConstants::RATINGS_FIELD)){
        $ratings = $comment->get(DsuRatingsReviewsConstants::RATINGS_FIELD);
        if(!empty($ratings->rating)){
          if(isset($results[$ratings->rating])){
            $results[$ratings->rating] ++;
          }else{
            $results[$ratings->rating] = 1;
          }
        }
      }
    }

    return $results;
  }

  public function getEntitiesIdsByEntityType($entity_type_id, $bundle = NULL){
    $type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundleType = $type->getKey('bundle');
    // Get all entity ids from entity type id (optional condition from bundle)
    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery()->accessCheck(TRUE);
    if($bundle){
      $query->condition($bundleType,$bundle);
    }
    $result = $query->execute();

    return !empty($result) ? $result : [];
  }

  public function getEntityTypeCommentResults($entity_type_id, $bundle){
    $entitiesIds = $this->getEntitiesIdsByEntityType($entity_type_id, $bundle);
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($entitiesIds);
    $results = [];
    /** @var ContentEntityInterface $entity */
    foreach ($entities as $entity){
      $countComments = count($this->getCommentsIdsByEntity($entity));
      if($countComments > 0){
        $results[$entity->id()] = $countComments;
      }
    }

    return $results;
  }

}
