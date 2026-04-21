<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'dsu_fivestar_stars' formatter.
 *
 * @FieldFormatter(
 *   id = "dsu_fivestar_stars",
 *   label = @Translation("Dsu Stars"),
 *   field_types = {
 *     "dsu_fivestar"
 *   },
 *   weight = 1
 * )
 */
class DsuStarsFormatter extends FormatterBase {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityFieldManager = $entityFieldManager;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_field.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();
    $form_builder = \Drupal::formBuilder();
    $display_settings = $this->getSettings();
    $display_settings['theme'] = 'dsu_fivestar_static';
    if (!$items->isEmpty()) {
      /** @var \Drupal\dsu_ratings_reviews\Plugin\Field\FieldType\DsuFivestarItem $item */
      foreach ($items as $delta => $item) {
        $context = [
          'entity' => $entity,
          'field_definition' => $item->getFieldDefinition(),
          'display_settings' => $display_settings,
        ];

        $elements[$delta] = $form_builder->getForm('\Drupal\dsu_ratings_reviews\Form\DsuFivestarForm', $context);
      }
    }
    // Load empty form ('No votes yet') if there are no items.
    else {
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityType()->id(), $entity->bundle());
      $field_definition = $bundle_fields[$items->getName()];

      $context = [
        'entity' => $entity,
        'field_definition' => $field_definition,
        'display_settings' => $display_settings,
      ];

      $elements[] = $form_builder->getForm('\Drupal\dsu_ratings_reviews\Form\DsuFivestarForm', $context);
    }

    return $elements;
  }

}
