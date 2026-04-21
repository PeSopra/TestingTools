<?php

namespace Drupal\dsu_ratings_reviews\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;
use Drupal\dsu_ratings_reviews\Form\RatingsReviewsSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller responses for Terms of use page.
 */
class RatingsReviewsTermsController extends ControllerBase {

  /**
   * Config factort service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AdminController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    $config = $this->configFactory->get(RatingsReviewsSettingsForm::SETTINGS);
    $tos = $config->get(DsuRatingsReviewsConstants::CONFIG_TOS);
    return [
      '#type'   => 'markup',
      '#markup' => $tos['value'] ?? '',
    ];
  }

}
