<?php

namespace Drupal\dsu_rr_nesgen\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Drupal\dsu_rr_nesgen\ConstantsInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * Represents a form for configuring settings
 * related to the dsu_rr_nesgen module.
 */
class SettingsForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;


  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
  ) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container): SettingsForm {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('config.typed'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'dsu_rr_nesgen_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('dsu_rr_nesgen.settings');

    $form['nesgen'] = [
      '#type' => 'details',
      '#title' => $this->t('Nesgen'),
      '#open' => TRUE,
    ];

    $form['nesgen']['prepromt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pre-promt'),
      '#config_target' => 'dsu_rr_nesgen.settings:nesgen.prepromt',
      '#description' => $this->t('You can specify a pre-prompt to give NesGen more context on how it should respond to the user.'),
    ];

    $form['nesgen']['sentiment'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sentiment'),
      '#options' => [
        ConstantsInterface::SENTIMENT_POSITIVE => $this->t('Positive'),
        ConstantsInterface::SENTIMENT_NEUTRAL => $this->t('Neutral'),
        ConstantsInterface::SENTIMENT_NEGATIVE => $this->t('Negative'),
      ],
      '#config_target' => 'dsu_rr_nesgen.settings:nesgen.sentiment',
      '#description' => $this->t('Please indicate in which cases the automatic response should be created using NesGen.'),
    ];

    $form['reply'] = [
      '#type' => 'details',
      '#title' => $this->t('Reply values'),
      '#open' => TRUE,
    ];

    $form['reply']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#size' => 200,
      '#config_target' => 'dsu_rr_nesgen.settings:reply.subject',
      '#description' => $this->t('Subject for replys created with NesGen'),
    ];

    $form['reply']['author'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Author'),
      '#required' => TRUE,
      '#config_target' => 'dsu_rr_nesgen.settings:reply.author',
      '#default_value' => $config->get('reply.author') ? $this->userStorage->load($config->get('reply.author')) : '',
      '#description' => $this->t('Select a Drupal user as the author for replies.'),
    ];

    $form['reply']['author_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author name'),
      '#size' => 200,
      '#config_target' => 'dsu_rr_nesgen.settings:reply.author_name',
      '#description' => $this->t('Please add the Brand name, or the desired text that will appear instead of the username in the response. If you leave it empty the username of the selected author will be used'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
