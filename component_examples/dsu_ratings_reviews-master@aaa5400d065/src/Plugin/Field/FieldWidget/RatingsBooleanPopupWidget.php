<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'boolean_checkbox' widget.
 *
 * @FieldWidget(
 *   id = "boolean_checkbox_popup",
 *   label = @Translation("Single on/off checkbox with Terms of use popup"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = TRUE
 * )
 */
class RatingsBooleanPopupWidget extends WidgetBase {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Renderer $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type'          => 'checkbox',
        '#default_value' => !empty($items[0]->value),
      ];

    $popup = [
      '#type'       => 'link',
      '#title'      => $this->t('Terms & Conditions'),
      '#url'        => Url::fromUri('internal:/ratings/tos'),
      '#attributes' => [
        'class'               => ['use-ajax', 'form-required'],
        'data-dialog-type'    => 'modal',
        'data-dialog-options' => Json::encode(['width' => '80%']),
      ],
    ];

    // Render popup link and embed on checkbox label text.
    try {
      $html = $this->renderer->render($popup);
    } catch (Exception $e) {
      $html = $this->t('Terms & Conditions');
    }
    $text = $this->t('I agree to the %terms', [
      '%terms' => $html,
    ]);
    $element['value']['#title'] = $text;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getTargetEntityTypeId() == 'comment' && $field_definition->getTargetBundle() == 'dsu_ratings_reviews_comment_type');
  }

}
