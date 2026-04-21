<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'dsu_fivestar_stars' widget.
 *
 * @FieldWidget(
 *   id = "dsu_fivestar_stars",
 *   label = @Translation("DSU Stars"),
 *   field_types = {
 *     "dsu_fivestar"
 *   }
 * )
 */
class DsuStarsWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $display_settings = $this->getSettings();
    $settings = $items[$delta]->getFieldDefinition()->getSettings();
    $display_settings += $settings;

    $is_field_config_form = ($form_state->getBuildInfo()['form_id'] == 'field_config_edit_form');
    $voting_is_allowed = (bool) ($settings['rated_while'] == 'editing') || $is_field_config_form;

    $element['rating'] = [
      '#type' => 'dsu_fivestar',
      '#stars' => $settings['stars'],
      '#allow_clear' => $settings['allow_clear'],
      '#allow_revote' => $settings['allow_revote'],
      '#allow_ownvote' => $settings['allow_ownvote'],
      '#default_value' => isset($items[$delta]->rating) ? $items[$delta]->rating : 0,
      '#settings' => $display_settings,
      '#show_static_result' => !$voting_is_allowed,
    ];
    return $element;
  }

}
