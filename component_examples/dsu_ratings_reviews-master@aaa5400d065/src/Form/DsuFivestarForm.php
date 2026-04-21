<?php

namespace Drupal\dsu_ratings_reviews\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\votingapi\VoteResultFunctionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dsu Fivestar form.
 */
class DsuFivestarForm extends FormBase {

  /**
   * The vote result manager.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $voteResultManager;

  /**
   * Form counter.
   *
   * @var int
   */
  protected static $formCounter = 0;

  /**
   * Creates a new object of this class.
   */
  public function __construct(VoteResultFunctionManager $vote_result_manager) {
    $this->voteResultManager = $vote_result_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.votingapi.resultfunction')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    self::$formCounter += 1;

    // For correct submit work set unique name for every form in page.
    return 'dsu_fivestar_form_' . self::$formCounter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $context = []) {
    $entity = $context['entity'];
    $uniq_id = Html::getUniqueId('vote');
    $field_definition = $context['field_definition'];
    $field_settings = $field_definition->getSettings();
    $field_name = $field_definition->getName();
    $voting_is_allowed = (bool) ($field_settings['rated_while'] == 'viewing');

    $form['vote'] = [
      '#type' => 'dsu_fivestar',
      '#stars' => $field_settings['stars'],
      '#allow_clear' => $field_settings['allow_clear'],
      '#allow_revote' => $field_settings['allow_revote'],
      '#allow_ownvote' => $field_settings['allow_ownvote'],
      '#default_value' => $entity->get($field_name)->rating,
      '#values' => $this->getResultsByVoteType($entity, $field_settings['vote_type']),
      '#settings' => $context['display_settings'],
      '#show_static_result' => !$voting_is_allowed,
      '#attributes' => [
        'class' => ['vote'],
      ],
    ];

    // Click on this element triggered from JS side.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rate'),
      '#ajax' => [
        'event' => 'click',
        'callback' => '::fivestarAjaxVote',
        'method' => 'replace',
        'wrapper' => $uniq_id,
        'effect' => 'fade',
      ],
      '#attributes' => [
        'style' => 'display:none',
      ],
    ];

    $form_state->set('context', $context);
    $form_state->set('uniq_id', $uniq_id);
    $form['#attributes']['id'] = $uniq_id;

    return $form;
  }

  /**
   * Ajax callback: update fivestar form after voting.
   */
  public function fivestarAjaxVote(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $context = $form_state->get('context');

    if (isset($context['entity'])) {
      $entity = $context['entity'];
      $fivestar_field_name = $context['field_definition']->getName();
      if ($entity->hasField($fivestar_field_name)) {
        // For votingapi value will be save during save rating value to
        // field storage.
        $entity->set($fivestar_field_name, $form_state->getValue('vote'));
        $entity->save();
      }
    }

    $form_state->setRebuild(TRUE);
  }

  private function getResultsByVoteType(FieldableEntityInterface $entity, $vote_type) {
    $results = $this->voteResultManager->getResults(
      $entity->getEntityTypeId(),
      $entity->id()
    );

    return $results[$vote_type] ?? [
      'vote_sum' => 0,
      'vote_user' => 0,
      'vote_count' => 0,
      'vote_average' => 0,
    ];
  }

}
