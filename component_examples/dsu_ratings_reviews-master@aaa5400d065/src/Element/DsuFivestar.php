<?php

namespace Drupal\dsu_ratings_reviews\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Template\Attribute;

/**
 * Provides a dsu fivestar form element.
 *
 * @FormElement("dsu_fivestar")
 */
class DsuFivestar extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#stars' => 5,
      '#allow_clear' => FALSE,
      '#allow_revote' => FALSE,
      '#allow_ownvote' => FALSE,
      '#ajax' => NULL,
      '#show_static_result' => FALSE,
      '#process' => [
        [$class, 'process'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#values' => [
        'vote_user' => 0,
        'vote_average' => 0,
        'vote_count' => 0,
      ],
      '#settings' => [],
    ];
  }

  /**
   * Process callback: process fivestar element.
   */
  public static function process(array &$element, FormStateInterface $form_state, &$complete_form) {
    $settings = $element['#settings'];
    $values = $element['#values'];
    $class[] = 'clearfix';
    $theme = $settings['theme'] ?? 'select';

    $options = ['-' => t('Select rating')];
    for ($i = 1; $i <= $element['#stars']; $i++) {
      $this_value = ceil($i * 100 / $element['#stars']);
      $options[$this_value] = t('select @star star out of @count', [
        '@star' => $i,
        '@count' => $element['#stars'],
      ]);
    }

    // Display clear button only if enabled.
    if ($element['#allow_clear'] == TRUE) {
      $options[0] = t('Cancel rating');
    }

    $element['vote'] = [
      '#type' => 'select',
      '#options' => $options,
      '#rating' => $values['vote_average'],
      '#required' => $element['#required'],
      '#attributes' => $element['#attributes'],
      '#theme' => $theme,
      '#default_value' => self::getElementDefaultValue($element),
      '#weight' => -2,
      '#ajax' => $element['#ajax'],
    ];

    if (isset($element['#parents'])) {
      $element['vote']['#parents'] = $element['#parents'];
    }
    $class[] = "fivestar-none-text";
    $class[] = 'fivestar-average-stars';


    $class[] = 'fivestar-form-item';
    $class[] = 'fivestar-basic';

    $element['#prefix'] = '<div ' . new Attribute(['class' => $class]) . '>';
    $element['#suffix'] = '</div>';

    if ($element['#show_static_result']) {
      // Dirty trick for omit error during rating save when voting is disabled.
      $element['vote']['#type'] = 'hidden';
      unset($element['vote']['#theme']);

      $element['vote_statistic'] = [
        '#theme' => 'dsu_fivestar_static',
        '#rating' => $element['vote']['#default_value'],
        '#stars' => $element['#stars'],
        '#vote_type' => $settings['vote_type'] ?? NULL,
      ];
    }

    $element['#attached']['library'][] = 'dsu_ratings_reviews/fivestar.base';

    return $element;
  }


  /**
   * Provides the correct default value for a fivestar element.
   *
   * @param array $element
   *   The fivestar element.
   *
   * @return float
   *   The default value for the element.
   */
  public static function getElementDefaultValue(array $element) {
    $default_value = $element['#default_value'] ?? 0;

    for ($i = 0; $i <= $element['#stars']; $i++) {
      $this_value = ceil($i * 100 / $element['#stars']);
      $next_value = ceil(($i + 1) * 100 / $element['#stars']);

      // Round up the default value to the next exact star value if needed.
      if ($this_value < $default_value && $next_value > $default_value) {
        $default_value = $next_value;
      }
    }

    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return $input;
  }

}
