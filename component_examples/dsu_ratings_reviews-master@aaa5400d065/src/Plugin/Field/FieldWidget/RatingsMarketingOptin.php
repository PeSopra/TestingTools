<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;
use Drupal\dsu_ratings_reviews\Form\RatingsReviewsSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'boolean_checkbox' widget.
 *
 * @FieldWidget(
 *   id = "dsu_ratings_marketing_optin",
 *   label = @Translation("Single on/off checkbox with Marketing opt-in text"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = TRUE
 * )
 */
class RatingsMarketingOptin extends WidgetBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get(RatingsReviewsSettingsForm::SETTINGS);
    if($config->get(DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN)){
      $element['value'] = $element + [
        '#type' => 'checkbox',
        '#default_value' => !empty($items[0]->value),
      ];
      $element['value']['#title'] = $config->get(DsuRatingsReviewsConstants::CONFIG_MARKETING_OPTIN);
    }else{
      $element['value'] = $element + [
          '#type' => 'hidden',
          '#value' => !empty($items[0]->value),
        ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getTargetEntityTypeId() == 'comment' && $field_definition->getTargetBundle() == 'dsu_ratings_reviews_comment_type');
  }

}
