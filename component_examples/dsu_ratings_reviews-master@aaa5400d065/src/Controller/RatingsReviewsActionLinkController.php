<?php

namespace Drupal\dsu_ratings_reviews\Controller;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\flag\Controller\ActionLinkController;
use Drupal\flag\FlagInterface;
use LogicException;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * Controller responses to flag and unflag action links.
 *
 * The response is a set of AJAX commands to update the
 * link in the page.
 */
class RatingsReviewsActionLinkController extends ActionLinkController {

  /**
   * {@inheritDoc}
   */
  public function flag(FlagInterface $flag, $entity_id, ?string $view_mode = NULL) {
    // Customize behaviour only for our flags.
    if (!in_array($flag->id(), DsuRatingsReviewsConstants::RATTINGS_REVIEWS_FLAGS)) {
      return parent::flag($flag, $entity_id, $view_mode);
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    try {
      $this->flagService->flag($flag, $entity);
    }
    catch (LogicException $e) {
      // Fail silently so we return to the entity, which will show an updated
      // link for the existing state of the flag.
    }

    // This method wasn't fully inherited to force using child class method.
    return $this->generateResponse($flag, $entity, $flag->getMessage('flag'), $view_mode);
  }

  /**
   * {@inheritDoc}
   */
  private function generateResponse(FlagInterface $flag, EntityInterface $entity, $message, ?string $view_mode = NULL) {
    // Create a new AJAX response.
    $response = new AjaxResponse();

    $this->generateCommand($flag, $entity, $message, $response, $view_mode);

    // Prepare reverse command as well, to update reverse button.
    $reverse_flag_id = DsuRatingsReviewsConstants::RATTINGS_REVIEWS_REVERSE_FLAGS[$flag->id()];
    if (!empty($reverse_flag_id)) {
      $reverse_flag = $this->flagService->getFlagById($reverse_flag_id);
      $this->generateCommand($reverse_flag, $entity, $message, $response, $view_mode);
    }

    return $response;
  }

  /**
   * Generates a response after the flag has been updated.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $message
   *   (optional) The message to flash.
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response object to complement.
   * @param string|null $view_mode
   *   The flaggable entity view mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   */
  private function generateCommand(FlagInterface $flag, EntityInterface $entity, string $message, AjaxResponse $response, ?string $view_mode = NULL) {
    // Get the link type plugin.
    $link_type = $flag->getLinkTypePlugin();

    // Generate the link render array.
    $link = $link_type->getAsFlagLink($flag, $entity, $view_mode);

    // Generate a CSS selector to use in a JQuery Replace command.
    $selector = '.js-flag-' . Html::cleanCssIdentifier($flag->id()) . '-' . $entity->id();

    // Create a new JQuery Replace command to update the link display.
    $replace = new ReplaceCommand($selector, $this->renderer->renderInIsolation($link));
    $response->addCommand($replace);

    // Push a message pulsing command onto the stack.
    $pulse = new ActionLinkFlashCommand($selector, $message);
    $response->addCommand($pulse);

    return $response;
  }

}
