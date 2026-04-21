<?php

namespace Drupal\dsu_ratings_reviews\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * Configure dsu_rating_reviews settings for this site.
 */
class RatingsReviewsSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'dsu_ratings_reviews.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dsu_rating_reviews_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form[DsuRatingsReviewsConstants::FIELD_GROUP_CONFIG] = [
      '#type'  => 'fieldgroup',
      '#title' => $this->t('Main configuration'),
    ];

    $formatted_text = $config->get(DsuRatingsReviewsConstants::CONFIG_TOS);
    $form[DsuRatingsReviewsConstants::FIELD_GROUP_CONFIG][DsuRatingsReviewsConstants::CONFIG_TOS] = [
      '#type'          => 'text_format',
      '#title'         => $this->t('Terms & conditions'),
      '#default_value' => $formatted_text['value'] ?: '',
      '#format'        => $formatted_text['format'] ?: '',
      '#description'   => $this->t('Please write the legal text the users would need to agree to write a review.'),
      '#required'      => TRUE,
    ];

    $form[DsuRatingsReviewsConstants::FIELD_MARKETING_OPTIN] = [
      '#type'        => 'fieldgroup',
      '#title'       => $this->t('Marketing opt-in'),
      '#description'   => $this->t('Check this option if you want to ask users who leave reviews if they can use them for commercial purposes.'),
    ];
    $form[DsuRatingsReviewsConstants::FIELD_MARKETING_OPTIN][DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable'),
      '#default_value' => $config->get(DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN),
    ];

    $form[DsuRatingsReviewsConstants::FIELD_MARKETING_OPTIN][DsuRatingsReviewsConstants::CONFIG_MARKETING_OPTIN] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Text'),
      '#default_value' => $config->get(DsuRatingsReviewsConstants::CONFIG_MARKETING_OPTIN),
      '#states' => [
        'required' => [
          ':input[name="' . DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN . '"]' => [
            'checked' => TRUE
          ],
        ],
        'visible' => [
          ':input[name="' . DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN . '"]' => [
            'checked' => TRUE
          ],
        ],
        'disabled' => [
          ':input[name="' . DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN . '"]' => [
            'checked' => FALSE
          ],
        ]
      ]
    ];

    $form[DsuRatingsReviewsConstants::FIELD_GROUP_MAIL] = [
      '#type'        => 'fieldgroup',
      '#title'       => $this->t('Moderator mail'),
      '#description' => $this->t('Select subject and body to notify administrators when a review is received.'),
    ];
    $form[DsuRatingsReviewsConstants::FIELD_GROUP_MAIL][DsuRatingsReviewsConstants::CONFIG_SUBJECT] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Subject'),
      '#description'   => $this->t('Mail subject to be used to notify administrators about the new rating.'),
      '#default_value' => $config->get(DsuRatingsReviewsConstants::CONFIG_SUBJECT),
      '#required'      => TRUE,
    ];

    $form[DsuRatingsReviewsConstants::FIELD_GROUP_MAIL][DsuRatingsReviewsConstants::CONFIG_BODY] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Body of the mail'),
      '#default_value' => $config->get(DsuRatingsReviewsConstants::CONFIG_BODY),
      '#required'      => TRUE,
    ];

    // Add the token tree UI.
    $form[DsuRatingsReviewsConstants::FIELD_GROUP_MAIL]['token_tree'] = [
      '#theme'           => 'token_tree_link',
      '#token_types'     => ['comment'],
      '#global_types'    => TRUE,
      '#show_restricted' => TRUE,
      '#weight'          => 90,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set(DsuRatingsReviewsConstants::CONFIG_TOS, $form_state->getValue(DsuRatingsReviewsConstants::CONFIG_TOS))
      ->set(DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN, $form_state->getValue(DsuRatingsReviewsConstants::CONFIG_ENABLE_MARKETING_OPTIN))
      ->set(DsuRatingsReviewsConstants::CONFIG_MARKETING_OPTIN, $form_state->getValue(DsuRatingsReviewsConstants::CONFIG_MARKETING_OPTIN))
      ->set(DsuRatingsReviewsConstants::CONFIG_SUBJECT, $form_state->getValue(DsuRatingsReviewsConstants::CONFIG_SUBJECT))
      ->set(DsuRatingsReviewsConstants::CONFIG_BODY, $form_state->getValue(DsuRatingsReviewsConstants::CONFIG_BODY))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

}
