<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TermsAcceptance constraint.
 */
class TermsConditionsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\comment\Entity\Comment $entity */
    $comment = $items->getEntity();
    foreach ($items as $item) {
      if (empty($item->value) && empty($comment->getParentComment())) {
        $this->context->addViolation($constraint->notAccepted, []);
      }
    }
  }

}
