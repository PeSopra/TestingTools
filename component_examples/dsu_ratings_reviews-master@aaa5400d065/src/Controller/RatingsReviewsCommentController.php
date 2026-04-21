<?php

namespace Drupal\dsu_ratings_reviews\Controller;

use Drupal\comment\Controller\CommentController;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * RatingsReviewsCommentController to customize specific comment type.
 */
class RatingsReviewsCommentController extends CommentController {

  /**
   * Access check for the reply form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function replyFormAccess(EntityInterface $entity, $field_name, $pid = NULL) {
    $access = parent::replyFormAccess($entity, $field_name, $pid);

    // If comment is dsu_ratings_reviews type, only brand can reply.
    if ($pid) {
      $comment = $this->entityTypeManager()->getStorage('comment')->load($pid);
      if ($comment->getTypeId() === DsuRatingsReviewsConstants::COMMENT_TYPE) {
        /** @var \Drupal\comment\CommentStorage $commentStorage */
        $commentStorage = $this->entityTypeManager->getStorage('comment');
        $children = $commentStorage->getChildCids([$comment->id() => $comment]);

        // Brand can only answer once, no siblings comments.
        $access = $access->andIf(AccessResult::allowedIf(empty($children)));
        $access = $access->andIf(AccessResult::allowedIfHasPermission($this->currentUser(), DsuRatingsReviewsConstants::REPLY_PERMISSION));
        $access = $access->andIf(AccessResult::allowedIf(!$comment->getParentComment()));
      }
    }
    return $access;
  }

}
