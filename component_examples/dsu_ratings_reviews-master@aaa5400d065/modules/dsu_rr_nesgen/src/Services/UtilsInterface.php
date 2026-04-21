<?php

namespace Drupal\dsu_rr_nesgen\Services;

use Drupal\comment\Entity\Comment;

/**
 * Interface for UtilsInterface service.
 */
interface UtilsInterface {

  /**
   * Analyze the review and create a response if necessary.
   *
   * @param \Drupal\comment\Entity\Comment $entity
   *   The comment entity.
   */
  public function analizeReview(Comment $entity): void;

}
