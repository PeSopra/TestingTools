<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that terms of use has been accepted with custom error message.
 *
 * @Constraint(
 *   id = "TermsAcceptance",
 *   label = @Translation("Terms and Conditions acceptance", context =
 *   "Validation"), type = "boolean"
 * )
 */
class TermsConditionsConstraint extends Constraint {

  /**
   * Message to show when user does not accept instead of HTML5 default.
   *
   * @var string
   */
  public $notAccepted = 'You must accept the terms and conditions to leave a review.';

}
