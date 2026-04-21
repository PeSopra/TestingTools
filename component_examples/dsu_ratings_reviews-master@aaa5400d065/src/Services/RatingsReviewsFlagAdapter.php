<?php

namespace Drupal\dsu_ratings_reviews\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagServiceInterface;
use LogicException;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * Class RatingsReviewsFlagAdapter.
 */
class RatingsReviewsFlagAdapter {

  /** @var \Drupal\flag\FlagServiceInterface */
  protected $flag;

  /**
   * @param \Drupal\flag\FlagServiceInterface $flag
   */
  public function __construct(FlagServiceInterface $flag){
    $this->flag = $flag;
  }

  /**
   * Returns list of customizable flags.
   *
   * @return string[]
   *   Flags this module controls.
   */
  public function getControlledFlags() {
    return [DsuRatingsReviewsConstants::FLAG_ID_USEFUL, DsuRatingsReviewsConstants::FLAG_ID_UNUSEFUL];
  }

  /**
   * Implements hook_entity_insert().
   *
   * Un-flags/flags reverse flag for DSU comments.
   */
  public function flaggingEntityInsert(EntityInterface $entity) {
    $flag_id = $entity->get('flag_id')->getValue();
    $flag_id = !empty($flag_id[0]['target_id']) ? $flag_id[0]['target_id'] : NULL;
    if (empty($flag_id)) {
      return;
    }

    // Get the opposite flag id.
    $reverse_flag_id = $this->getReverseFlag($flag_id);

    // Get the flagging entity and un-flag opposite flag.
    /** @var \Drupal\flag\Entity\Flagging $entity */
    $flaggable_entity = $entity->getFlaggable();
    $reverse_flag = $this->flag->getFlagById($reverse_flag_id);
    try {
      $this->flag->unflag($reverse_flag, $flaggable_entity);
    } catch (LogicException $e) {
    }
  }

  /**
   * Returns a reverse flag identifier if exists for this flag.
   *
   * @param string $flag_id
   *   Id of the flag to find reverse for.
   *
   * @return string
   *   Id of the opposite flag, if any.
   */
  public function getReverseFlag(string $flag_id) {

    return $flag_id === DsuRatingsReviewsConstants::FLAG_ID_USEFUL ? DsuRatingsReviewsConstants::FLAG_ID_UNUSEFUL : DsuRatingsReviewsConstants::FLAG_ID_USEFUL;
  }

}
