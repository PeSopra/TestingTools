<?php

namespace Drupal\dsu_ratings_reviews\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dsu_ratings_reviews\Services\RatingsReviewsDisplayAdapter;
use Drupal\dsu_ratings_reviews\Services\RatingsReviewsUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Serializer;

class DashboardForm extends FormBase{

  const ENTITY_TYPE_ID = 'node';

  /**
   * @var \Drupal\dsu_ratings_reviews\Services\RatingsReviewsUtils
   */
  protected $ratingsReviewsUtils;

  /**
   * @var \Drupal\dsu_ratings_reviews\Services\RatingsReviewsDisplayAdapter
   */
  protected $ratingsReviewsDisplayAdapter;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var Serializer
   */
  protected $serializer;


  /**
   * @param RatingsReviewsUtils $ratingsReviewsUtils
   * @param RatingsReviewsDisplayAdapter $ratingsReviewsDisplayAdapter
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param Serializer $serializer
   */
  public function __construct(RatingsReviewsUtils $ratingsReviewsUtils, RatingsReviewsDisplayAdapter $ratingsReviewsDisplayAdapter, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, Serializer $serializer){
    $this->ratingsReviewsUtils = $ratingsReviewsUtils;
    $this->ratingsReviewsDisplayAdapter = $ratingsReviewsDisplayAdapter;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ratings_reviews.utils'),
      $container->get('ratings_reviews.display_adapter'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('serializer')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'dsu_ratings_reviews_dashboard';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
      '#tree' => TRUE,
      '#attributes' => [
        'class' => ['container-inline']
      ]
    ];
    $form['filters']['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#options' => $this->getEntityBundlesWithComments(self::ENTITY_TYPE_ID),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateEntityAutocomplete',
        'wrapper' => 'entity-wrapper',
        'event' => 'change',
      ],
    ];
    $bundle = $form_state->getValue(['filters','bundle']);
    $userInput = $form_state->getUserInput();
    $userInput['filters']['entity_wrapper']['entity_id'] = '';
    $form_state->setUserInput($userInput);
    $form_state->setValue(['filters','entity_wrapper','entity_id'],'');
    $form['filters']['entity_wrapper'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'entity-wrapper'
      ],
    ];
    if(!empty($bundle)){
      $form['filters']['entity_wrapper']['entity_id'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => self::ENTITY_TYPE_ID,
        '#selection_handler' => 'default',
        '#selection_settings' => [
          'target_bundles' => empty($bundle) ? [] : [$bundle],
        ],
        '#title' => t('Entity'),
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate report'),
      '#ajax' => [
        'callback' => '::updateResults',
        'wrapper' => 'result-wrapper',
      ],
    ];
    $form['actions']['download'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download report'),
      '#submit' => ['::downloadResults']
    ];

    $form['result'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'result-wrapper'
      ],
      '#weight' => 100,
    ];


    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function updateEntityAutocomplete(array &$form, FormStateInterface $form_state) {
    return $form['filters']['entity_wrapper'];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateResults(array &$form, FormStateInterface $form_state) {
    $form['result']['value'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      'results' => $this->getResults($form,$form_state),
    ];

    return $form['result'];
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function downloadResults(array &$form, FormStateInterface $form_state) {
    $bundle = $form_state->getValue(['filters','bundle']);
    $entity_id = $form_state->getValue(['filters','entity_wrapper','entity_id']);
    $statistics = $this->getStatistics($bundle,$entity_id);
    $filename = "statistics_" . rand() . ".xls";
    $response = new StreamedResponse(function () use ($statistics) {
      echo $this->serializer->serialize([$statistics], 'xls');
    });
    $response->headers->set('Content-Type', 'application/vnd.ms-excel');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    $form_state->setResponse($response);
  }

  /**
   * @param $entity_type_id
   *
   * @return array
   */
  private function getEntityBundlesWithComments($entity_type_id){
    $bundles = array_map(['\Drupal\Component\Utility\Html', 'escape'], $this->entity_type_get_names($entity_type_id));
    foreach ($bundles as $bundle=>$label){
      $commentsIds = $this->ratingsReviewsUtils->getCommentsIdsByEntityType(self::ENTITY_TYPE_ID,$bundle, NULL);
      if(empty($commentsIds)){
        unset($bundles[$bundle]);
      }
    }

    return $bundles;
  }





  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getResults(array &$form, FormStateInterface $form_state){
    $bundle = $form_state->getValue(['filters','bundle']);
    if(empty($bundle) || $form_state->getErrors()){
      return [];
    }
    $entity_id = $form_state->getValue(['filters','entity_wrapper','entity_id']);
    $statistics = $this->getStatistics($bundle,$entity_id);
    return [
      '#type'   => '#theme',
      '#theme'  => 'page_statistics',
      '#data'   => $statistics,
      '#entity'   => empty($entity_id) ? NULL : $this->entityTypeManager->getStorage(self::ENTITY_TYPE_ID)->load($entity_id),
      '#attached' =>[
        'library' => ['dsu_ratings_reviews/dsu-rating-dashboard']
      ]
    ];
  }

  /**
   * @param $bundle
   * @param $entity_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getStatistics($bundle, $entity_id = NULL) {
    if($entity_id){
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage(self::ENTITY_TYPE_ID)->load($entity_id);
      $totalEntities = (int) $entity->isPublished();
      $votingResult = $this->ratingsReviewsUtils->getEntityVotingsResults($entity);
      $recommendations = count($this->ratingsReviewsUtils->getRecommendedCommentsIdsByEntity($entity));
      $published = count($this->ratingsReviewsUtils->getCommentsIdsByEntity($entity));
      $unpublished = count($this->ratingsReviewsUtils->getCommentsIdsByEntity($entity, 0));
      $entitiesWithReviews = (int) count($this->ratingsReviewsUtils->getCommentsIdsByEntity($entity)) > 0;
    }else{
      $votingResult = $this->ratingsReviewsUtils->getEntityTypeVotingsResults(self::ENTITY_TYPE_ID,$bundle);
      $recommendations = count($this->ratingsReviewsUtils->getRecommendedCommentsIdsByEntityType(self::ENTITY_TYPE_ID, $bundle));
      $totalEntities = count($this->ratingsReviewsUtils->getEntitiesIdsByEntityType(self::ENTITY_TYPE_ID, $bundle));
      $published = count($this->ratingsReviewsUtils->getCommentsIdsByEntityType(self::ENTITY_TYPE_ID,$bundle));
      $unpublished = count($this->ratingsReviewsUtils->getCommentsIdsByEntityType(self::ENTITY_TYPE_ID,$bundle,0));
      $entitiesWithReviews = count($this->ratingsReviewsUtils->getEntityTypeCommentResults(self::ENTITY_TYPE_ID,$bundle));
    }
    // Join all data to visualize.
    $type_names = $this->entity_type_get_names(self::ENTITY_TYPE_ID);
    $votes = [
      '20'  => 0,
      '40'  => 0,
      '60'  => 0,
      '80'  => 0,
      '100' => 0,
    ];
    $votes = $votingResult + $votes;
    $sum = 0;
    foreach ($votes as $value => $count) {
      $sum = $sum + ($value * $count);
    }
    $total = array_sum(array_values($votes));
    $average = $total ? (float) $sum / (float) $total : 0;
    $fourStarVotesPercentage = $total ? (($votes['80'] + $votes['100']) / $total) : 0;
    $recommendationsPercentage = $total ? ($recommendations / $total) : 0;
    $entitiesWithoutReviews = $totalEntities - $entitiesWithReviews;
    $result = [
      'label'                            => $type_names[$bundle],
      'average'                          => number_format($average / 20, 2),
      'totalVotes'                       => $total,
      'fourStarVotesPercentage'          => number_format($fourStarVotesPercentage, 2) * 100,
      'recommendationsPercentage'        => number_format($recommendationsPercentage, 2) * 100,
      'published'                        => $published,
      'unpublished'                      => $unpublished,
      'entitiesWithReviews'              => !empty($totalEntities) ? $entitiesWithReviews : 0,
      'entitiesWithoutReviews'           => !empty($totalEntities) ? $entitiesWithoutReviews : 0,
      'entitiesWithReviewsPercentage'    => !empty($totalEntities) ? number_format(($entitiesWithReviews / $totalEntities), 2) * 100 : 0,
      'entitiesWithoutReviewsPercentage' => !empty($totalEntities) ? number_format(($entitiesWithoutReviews / $totalEntities), 2) * 100 : 0,
      'totalEntities'                    => $totalEntities,
    ];

    return $result;
  }

  /**
   * Returns a list of available entity type bundle names.
   *
   * This list can include types that are queued for addition or deletion.
   *
   * @return string[]
   *   An array of node type labels, keyed by the entity type bundle name.
   */
  private function entity_type_get_names($entity_type_id) {
    return array_map(function ($bundle_info) {
      return $bundle_info['label'];
    }, $this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
  }

}
