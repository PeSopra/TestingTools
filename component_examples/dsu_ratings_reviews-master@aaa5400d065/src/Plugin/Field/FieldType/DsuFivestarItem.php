<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\user\EntityOwnerInterface;

/**
 * Plugin implementation of the 'fivestar' field type.
 *
 * @FieldType(
 *   id = "dsu_fivestar",
 *   label = @Translation("Dsu Fivestar rating"),
 *   description = @Translation("Store a rating for this piece of content."),
 *   category = "lightnest",
 *   no_ui = TRUE,
 *   default_widget = "dsu_fivestar_stars",
 *   default_formatter = "dsu_fivestar_stars",
 * )
 */
class DsuFivestarItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'rating' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
          'sortable' => TRUE,
        ],
        'target' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $property_definitions['rating'] = DataDefinition::create('integer')
      ->setLabel(t('Rating'));
    $property_definitions['target'] = DataDefinition::create('integer')
      ->setLabel(t('Target'));
    return $property_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'rating';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'stars' => 5,
      'allow_clear' => FALSE,
      'allow_revote' => TRUE,
      'allow_ownvote' => TRUE,
      'rated_while' => 'viewing',
      'enable_voting_target' => FALSE,
      'target_bridge_field' => '',
      'target_fivestar_field' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'vote_type' => 'vote',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $vote_types_link = Link::createFromRoute($this->t('here'), 'entity.vote_type.collection')->toString();

    $element['vote_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Vote type'),
      '#options' => $this->getVoteTypes(),
      '#description' => $this->t('The vote type this rating will affect. Enter a property on which that this rating will affect, such as <em>quality</em>, <em>satisfaction</em>, <em>overall</em>, etc. You can add new vote type %vote_types_link.', [
        '%vote_types_link' => $vote_types_link,
      ]),
      '#default_value' => $this->getSetting('vote_type'),
      '#show_static_result' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['stars'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of stars'),
      '#options' => array_combine(range(1, 10), range(1, 10)),
      '#default_value' => $this->getSetting('stars'),
    ];
    $element['allow_clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to cancel their ratings.'),
      '#default_value' => $this->getSetting('allow_clear'),
      '#return_value' => 1,
    ];
    $element['allow_revote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to re-vote on already voted content.'),
      '#default_value' => $this->getSetting('allow_revote'),
      '#return_value' => 1,
    ];
    $element['allow_ownvote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to vote on their own content.'),
      '#default_value' => $this->getSetting('allow_ownvote'),
      '#return_value' => 1,
    ];
    $element['rated_while'] = [
      '#type' => 'radios',
      '#default_value' => $this->getSetting('rated_while'),
      '#title' => $this->t('Select when user can rate the field'),
      '#options' => [
        'viewing' => 'Rated while viewing',
        'editing' => 'Rated while editing',
      ],
    ];
    $element['enable_voting_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set voting target'),
      '#default_value' => $this->getSetting('enable_voting_target'),
    ];
    $states = [
      'visible' => [
        ':input[name="settings[enable_voting_target]"]' => ['checked' => TRUE],
      ],
      'required' => [
        ':input[name="settings[enable_voting_target]"]' => ['checked' => TRUE],
      ],
    ];
    $element['target_bridge_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target bridge field'),
      '#description' => $this->t(
        'Machine name of field that binds current entity with entity that contain target fivestar field.
        The field should have "entity_reference" type.'
      ),
      '#states' => $states,
      '#default_value' => $this->getSetting('target_bridge_field'),
    ];
    $element['target_fivestar_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target fivestar field'),
      '#description' => $this->t('Machine name of fivestar field which should affect after vote.'),
      '#states' => $states,
      '#default_value' => $this->getSetting('target_fivestar_field'),
    ];
    $element['#element_validate'] = [
      [get_class($this), 'fieldSettingsFormValidate'],
    ];

    // @todo try to find the way to omit it.
    $form_state->set('host_entity', $this->getEntity());

    return $element;
  }

  /**
   * Validate callback: check field settings.
   */
  public static function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $host_entity = $form_state->get('host_entity');
    $field_settings = $form_state->getValue('settings');

    // Validate voting target settings.
    if ($field_settings['enable_voting_target'] == 1) {
      // Check if bridge field exist.
      if (!$host_entity->hasField($field_settings['target_bridge_field'])) {
        $form_state->setErrorByName(
          'target_bridge_field',
          t('The host entity doesn\'t contain field: "@field_name"', [
            '@field_name' => $field_settings['target_bridge_field'],
          ])
        );
        return;
      }

      // Check if bridge field has correct type.
      $field_type = $host_entity->get($field_settings['target_bridge_field'])->getFieldDefinition()->getType();
      if ($field_type != 'entity_reference') {
        $form_state->setErrorByName(
          'target_bridge_field',
          t('The bridge field must have "entity_reference" type. The entered field has type: "@field_type"', [
            '@field_type' => $field_type,
          ])
        );
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $rating = $this->get('rating')->getValue();
    return empty($rating) || $rating == '-';
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $entity = $this->getEntity();
    $field_definition = $this->getFieldDefinition();
    $field_settings = $field_definition->getSettings();
    $target_entity = $this->getTargetEntity($entity, $field_settings);
    $vote_rating = $entity->get($field_definition->getName())->rating ?: 0;

    $owner = $this->getVoteOwner($entity, $field_settings['rated_while']);

    if ($update) {
      // Delete previous votes.
      $criteria = [
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'type' => $field_settings['vote_type'],
        'user_id' => $owner->id(),
      ];
      if ($owner->isAnonymous()) {
        $ip_address = \Drupal::request()->getClientIp();
        $criteria['vote_source'] = hash('sha256', serialize($ip_address));
      }
      foreach ($this->getVotesByCriteria($criteria) as $vote) {
        $vote->delete();
      }

      // Delete votes from target entity.
      if (!empty($target_entity)) {
        $criteria['entity_id'] = $target_entity->id();
        $criteria['entity_type'] = $target_entity->getEntityTypeId();
        foreach ($this->getVotesByCriteria($criteria) as $target_vote) {
          $target_vote->delete();
        }
      }
    }

    // Add new vote.
    $this->addVote($entity, $vote_rating, $field_settings['vote_type'], $owner->id());
    if (!empty($target_entity) && $entity->isPublished()) {
      $this->addVote(
        $target_entity,
        $vote_rating,
        $field_settings['vote_type'],
        $owner->id()
      );
    }

    // No changes made to the Fivestar field item in this method.
    return FALSE;
  }

  /**
   * Get target entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param array $field_settings
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   */
  public function getTargetEntity(FieldableEntityInterface $entity, array $field_settings) {
    if ($field_settings['enable_voting_target'] !== TRUE) {
      return NULL;
    }
    if (!$entity->hasField($field_settings['target_bridge_field'])) {
      return NULL;
    }

    $bridge_entity = $entity->{$field_settings['target_bridge_field']}->entity;
    if ($bridge_entity && $bridge_entity->hasField($field_settings['target_fivestar_field'])) {
      return $bridge_entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $del_entity = $this->getEntity();
    $field_settings = $this->getFieldDefinition()->getSettings();
    $target_entity = $this->getTargetEntity($del_entity, $field_settings);

    if (!$target_entity) {
      return;
    }

    $vote_storage = \Drupal::entityTypeManager()->getStorage('vote');
    $votes = $vote_storage->loadByProperties([
      'entity_type' => $del_entity->getEntityTypeId(),
      'entity_id' => $del_entity->id(),
    ]);

    foreach ($votes as $vote) {
      // Get target vote.
      $target_votes = $vote_storage->loadByProperties([
        'entity_type' => $target_entity->getEntityTypeId(),
        'entity_id' => $target_entity->id(),
        'type' => $vote->bundle(),
        'user_id' => $vote->getOwnerId(),
        'value' => $vote->getValue(),
        'vote_source' => $vote->getSource(),
      ]);

      foreach ($target_votes as $target_vote) {
        $target_vote->delete();
      }
    }
  }

  /**
   * Get owner for vote.
   *
   * In order to get correct vote owner need to do it based on fivestar field
   * settings, when selected "Rating mode viewing" mode, then have to use
   * current user. For "Rating mode editing" mode - if entity have method
   * "getOwner" use entity owner, otherwise the current user has to be used.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity from which try to get owner.
   * @param string $rating_mode
   *   Determines under what conditions a user can leave a review.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account of the vote owner.
   */
  protected function getVoteOwner(FieldableEntityInterface $entity, $rating_mode) {
    switch ($rating_mode) {
      case 'editing':
        if ($entity instanceof EntityOwnerInterface) {
          return $entity->getOwner();
        }

      // Fall through.
      case 'viewing':
      default:
        return \Drupal::currentUser();
    }
  }

  private function getVoteTypes() {
    $options = [];
    $vote_type_storage = \Drupal::entityTypeManager()->getStorage('vote_type');

    foreach ($vote_type_storage->loadMultiple() as $vote_type) {
      $options[$vote_type->id()] = $vote_type->label();
    }

    return $options;
  }

  /**
   * Get votes by criteria.
   *
   * @param array $criteria
   *   Associative array of criteria. Keys are:
   *   - entity_id: The entity id.
   *   - entity_type: The entity type.
   *   - type: Vote type.
   *   - user_id: The user id.
   *   - vote_source: The vote source.
   *
   * @return array
   *   Which contain vote ids.
   */
  private function getVotesByCriteria(array $criteria) {
    if (empty($criteria)) {
      return [];
    }

    return \Drupal::entityTypeManager()->getStorage('vote')->loadByProperties($criteria);
  }

  /**
   * Add vote.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param int $rating
   * @param string $vote_type
   * @param int|null $uid
   *
   * @return \Drupal\votingapi\Entity\Vote
   */
  private function addVote(FieldableEntityInterface $entity, $rating, $vote_type = 'vote', $uid = NULL) {
    $uid = is_numeric($uid) ? $uid : \Drupal::currentUser()->id();
    $rating = ($rating > 100) ? 100 : $rating;
    /** @var \Drupal\votingapi\Entity\Vote $vote */
    $vote = \Drupal::entityTypeManager()->getStorage('vote')->create(['type' => $vote_type]);
    $vote->setVotedEntityId($entity->id());
    $vote->setVotedEntityType($entity->getEntityTypeId());
    $vote->setOwnerId($uid);
    $vote->setValue($rating);
    $vote->save();

    return $vote;
  }

}
