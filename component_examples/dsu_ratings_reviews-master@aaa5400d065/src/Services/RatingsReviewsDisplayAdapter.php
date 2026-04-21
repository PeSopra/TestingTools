<?php

namespace Drupal\dsu_ratings_reviews\Services;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\dsu_ratings_reviews\Element\DsuFivestar;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * Class RatingsReviewsDisplayAdapter.
 */
class RatingsReviewsDisplayAdapter{

  use StringTranslationTrait;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $moduleExtensionList;

  /**
   * @var \Drupal\dsu_ratings_reviews\Services\RatingsReviewsUtils
   */
  protected $ratingsReviewsUtils;

  /**
   * RatingsReviewsAdaptations constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   * @param \Drupal\Core\Extension\ExtensionList $moduleExtensionList
   * @param \Drupal\dsu_ratings_reviews\Services\RatingsReviewsUtils $ratingsReviewsUtils
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, CurrentRouteMatch $route_match, ExtensionList $moduleExtensionList, RatingsReviewsUtils $ratingsReviewsUtils) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->routeMatch = $route_match;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->ratingsReviewsUtils = $ratingsReviewsUtils;
  }

  /**
   * Implements hook_comment_links_alter().
   *
   * Hides reply button on rating comments for non-admins/brands.
   */
  public function commentLinksAlter(array &$links, CommentInterface $entity, array &$context) {
    // Allow just one level of depth in comments.
    if (!empty($entity->getParentComment())) {
      unset($links['comment']['#links']['comment-reply']);
    }
    else {
      /** @var \Drupal\comment\CommentStorage $commentStorage */
      $commentStorage = $this->entityTypeManager->getStorage('comment');
      $children = $commentStorage->getChildCids([$entity->id() => $entity]);
      $access = $this->currentUser->hasPermission(DsuRatingsReviewsConstants::REPLY_PERMISSION);

      // Also limit replies to just one, and only for the brand.
      if (!empty($children) || !$access) {
        unset($links['comment']['#links']['comment-reply']);
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Customizes comment reply form for the rating comment.
   */
  public function formCommentFormAlter(&$form, &$form_state, $form_id) {
    // Render Yes option before the No option on field_dsu_recommend.
    if(isset($form[DsuRatingsReviewsConstants::RECOMMEND_FIELD]['widget']['#options'])){
      $recommendOptions = $form[DsuRatingsReviewsConstants::RECOMMEND_FIELD]['widget']['#options'];
      $form[DsuRatingsReviewsConstants::RECOMMEND_FIELD]['widget']['#options'] = array_reverse($recommendOptions,TRUE);
    }
    // Send event tracking for getting product from ratings reviews.
    $info = $this->moduleExtensionList->getExtensionInfo('dsu_ratings_reviews');
    $form['#attached']['drupalSettings']['dsu_ratings_reviews']['data'] = [
      'module_name' => $info['name'],
      'module_version' => $info['version'],
    ];
    $form['#attached']['library'][] = 'dsu_ratings_reviews/dsu-event-tracking';

    $form['#validate'][] = self::class . '::validateCommentForm';
    $form['actions']['submit']['#submit'][] = self::class . '::submitCommentForm';
  }

  /**
   * Implements hook_entity_view_alter().
   *
   * Customizes entity view display to remove votes on replies.
   */
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    // Send event tracking for getting product from ratings reviews.
    $info = $this->moduleExtensionList->getExtensionInfo('dsu_ratings_reviews');
    $build['#attached']['drupalSettings']['dsu_ratings_reviews']['data'] = [
      'module_name' => $info['name'],
      'module_version' => $info['version'],
    ];
    $build['#attached']['library'][] = 'dsu_ratings_reviews/dsu-event-tracking';
    // Any reply to rating comments does not include vote and will use the
    // display field instead of the username.
    $build['#attributes']['class'][] = 'comment--' . $entity->isPublished() ? 'published' : 'unpublished';
    if($entity->getOwner()->isAnonymous()){
      $build['#attributes']['class'][] = 'by-anonymous';
    }
    if($commented_entity = $entity->getCommentedEntity()){
      $build['#attributes']['class'][] = 'by-' . $commented_entity->getEntityTypeId() . '-author';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for views_exposed_form.
   *
   * Modify exposed form for the dsu_comments view.
   */
  public function commentsExposedFormAlter(&$form, $form_state, $form_id) {
    /** @var \Drupal\views\Entity\View $storageView */
    $viewStorage = $form_state->getStorage('view');
    $view = isset($viewStorage['view']) ? $viewStorage['view'] : NULL;
    $nid = $view->args[0];
    /** @var NodeInterface $node */
    if($node = $this->entityTypeManager->getStorage('node')->load($nid)){
      $info = $this->getCommentsStatistics($node);
      // Show rating form widget just for results.
      $this->setFivestarWidget($form, $info);
      // Show progress bars on radio button with results.
      $this->alterRatingRadioButtons($form, $info);
    }
    // Use checkboxes instead of dropdowns.
    $this->alterDropdowns($form);
    // With everything prepared, add automatic submission.
    $this->addAutoSubmission($form);

    // Change -Any- option name to All Stars.
    $form['stars']['#options']['All'] = t('All Stars');
  }

  /**
   * Hide the dropdownds for filters and use checkboxes to manage the state.
   *
   * @param array $form
   *   Array with form definition.
   */
  protected function alterDropdowns(array &$form) {
    $form['recommend']['#type'] = 'hidden';
    $form['recommend_checkbox'] = [
      '#type'  => 'checkbox',
      '#title' => t('Recommended'),
      '#attributes' => [
        'role' => 'button'
      ],
    ];

    // Count created.
    $form['sort_by']['#type'] = 'hidden';
    // ASC DESC.
    $form['sort_order']['#type'] = 'hidden';
    $form['sort_by_useful_checkbox'] = [
      '#type'  => 'checkbox',
      '#title' => t('Most useful first'),
      '#attributes' => [
        'role' => 'button'
      ],
    ];
  }

  /**
   * Add the autosubmition functionality for the form.
   *
   * @param array $form
   *   Form array.
   */
  protected function addAutoSubmission(array &$form) {
    $form['stars']['#attributes']['class'][] = 'auto-submit-click';
    $form['recommend']['#attributes']['class'][] = 'auto-submit-click';

    $form['#attached']['library'][] = 'dsu_ratings_reviews/ratings-autosubmit';
  }

  /**
   * Implements hook_preprocess_node_links_alter().
   *
   * Modifies the "Add new comment" text for our comment type.
   */
  public function nodeLinksAlter(array &$links, NodeInterface $entity, array &$context) {
    // Find all comment fields with add button.
    $fields = [];
    foreach ($links as $key => $value) {
      if (strpos($key, 'comment__') === 0 && isset($links[$key]['#links']['comment-add'])) {
        $fields[] = str_replace('comment__', '', $key);
      }
    }

    // Filter those that are not DSU ones and replace text.
    foreach ($fields as $field_name) {
      $storage = $entity->get($field_name)
        ->getFieldDefinition()
        ->getFieldStorageDefinition();
      if ($storage->getSetting('comment_type') === DsuRatingsReviewsConstants::COMMENT_TYPE) {
        $links['comment__' . $field_name]['#links']['comment-add']['title'] = t('Write a review');
      }
    }
  }

  private function getCommentsStatistics(ContentEntityInterface $entity){
    $comment_ids = $this->ratingsReviewsUtils->getCommentsIdsByEntity($entity);
    $recommendedCommentsIds = $this->ratingsReviewsUtils->getRecommendedCommentsIdsByEntity($entity);
    $votingsResults = $this->ratingsReviewsUtils->getCommentsVotingsResults($comment_ids);
    $sum_votes = 0;
    foreach ($votingsResults as $average=>$votes){
      $sum_votes += ($average * $votes);
    }
    $statistics['results'] = $votingsResults;
    $vote_count = count($comment_ids);
    $statistics['total'] = $vote_count;
    $statistics['average'] = $vote_count > 0 ? ($sum_votes / $vote_count) : 0;
    $statistics['avg_recommended_count'] = !empty($comment_ids) ? (int) round((count($recommendedCommentsIds) / count($comment_ids)) * 100) : 0;

    return $statistics;
  }

  /**
   * Alters form to show a blocked Fivestar Form Widget to show rating.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param array $info
   *   Information array with voting results.
   */
  private function setFivestarWidget(array &$form, array $info) {
    $form['current'] = [
      '#input'              => TRUE,
      '#type'               => 'dsu_fivestar',
      '#stars'              => 5,
      '#allow_clear'        => FALSE,
      '#description'        => $this->t('@recommended_count Recommended', [
        '@recommended_count' => $info['avg_recommended_count'].'%',
      ]),
      '#allow_revote'       => FALSE,
      '#allow_ownvote'      => FALSE,
      '#ajax'               => NULL,
      '#show_static_result' => FALSE,
      '#process'            => [
        [DsuFivestar::class, 'process'],
        [DsuFivestar::class, 'processAjaxForm'],
      ],
      '#theme_wrappers'     => ['form_element'],
      '#widget'             => [
        'name' => 'default',
      ],
      '#values'             => [
        'vote_user'    => 0,
        'vote_average' => $info['average'],
        'vote_count'   => $info['total'],
      ],
      '#settings'           => [
        'theme' => 'dsu_fivestar_static'
      ],
      '#weight'             => -1,
      '#field_prefix'       => $this->t('<span class="dsu_rr-ratings_average">@star_number</span> out of @star_total', [
        '@star_number' => number_format(($info['average'] / 100) * 5, 1),
        '@star_total'  => 5,
      ]),
      '#field_suffix'       => $this->t('@total_count reviews', [
        '@total_count' => $info['total'],
      ]),
    ];
  }

  /**
   * Alters radio buttons in view exposed form to show ratings and progress bar.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param array $info
   *   Information array with voting results.
   */
  private function alterRatingRadioButtons(array &$form, array $info) {
    // Transform rating from fivestar to final radio buttons format.
    $stars = [];
    $stars['All'] = $info['total'];
    $map = ['All', '100', '80', '60', '40', '20'];
    foreach ($map as $key => $expected_value) {
      $stars[$key] = $info['results'][$expected_value] ?? 0;
    }
    // Add progress bar as suffix.
    foreach ($form['stars']['#options'] as $option_key => $option_markup) {
      $progress = $stars[$option_key] ?? 0;
      $form['stars'][$option_key]['#field_suffix'] = '<progress id="file" value="' . $progress . '" max="' . $info['total'] . '">' . $progress . '</progress><span class="rating">' . $progress . '</span>';
    }
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   *
   * Add a custom text constraint to terms of use checkbox field.
   */
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle) {
    if ($entity_type->id() === 'comment' && $bundle === DsuRatingsReviewsConstants::COMMENT_TYPE) {
      if (isset($fields[DsuRatingsReviewsConstants::TOS_FIELD])) {
        $fields[DsuRatingsReviewsConstants::TOS_FIELD]->addConstraint('TermsAcceptance', []);
      }
    }
  }

  /**
   * Implements hook_views_query_alter().
   *
   * Hides unpublished comments when the user don't have permissions.
   */
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {
    if ($this->currentUser->hasPermission(DsuRatingsReviewsConstants::REPLY_PERMISSION)) {
      foreach ($query->where as &$condition_group) {
        foreach ($condition_group['conditions'] as $key => $condition) {
          if ($condition['field'] === 'comment_field_data.status') {
            unset($condition_group['conditions'][$key]);
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_form_display_alter().
   *
   * Modifies reply comments to use its own form mode.
   */
  public function entityFormDisplayAlter(&$form_display, $context) {
    $route_name = $this->routeMatch->getRouteName();
    // Check if its a reply form, either new or editing.
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = $this->routeMatch->getParameter('comment');
    $pid = $this->routeMatch->getParameter('pid');
    $is_new_reply = $route_name === 'comment.reply' && !empty($pid);
    $is_editing_reply = !empty($comment) && !empty($comment->getParentComment());
    // If so, change form display to our reply one.
    if ($is_new_reply || $is_editing_reply) {
      // If this is a reply, new or edited, show reply form mode.
      $storage = $this->entityTypeManager->getStorage('entity_form_display');
      $form_display = $storage->load('comment.' . DsuRatingsReviewsConstants::COMMENT_TYPE . '.reply');
    }
  }

  /**
   * Implements hook_entity_presave().
   *
   * Alters comments so replies are always published by default.
   * Also check ratings are always within values and/or 4-stars.
   */
  public function entityPresave(EntityInterface $entity) {
    $pid = $entity->get('pid')->getValue();
    $rating = $entity->get(DsuRatingsReviewsConstants::RATINGS_FIELD)->getValue();

    if (empty($rating[0]['rating']) && empty($pid)) {
      $rating[0]['rating'] = '80';
      $entity->set(DsuRatingsReviewsConstants::RATINGS_FIELD, $rating);
    }
  }

  /**
   * Validate handler for comment form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateCommentForm(array &$form, FormStateInterface $form_state): void {
    // Set event errors.
    foreach ($form_state->getErrors() as $key => $err) {
      if (\Drupal::hasService('ln_datalayer.events')) {
        $info = \Drupal::service('extension.list.module')->getExtensionInfo('dsu_ratings_reviews');
        \Drupal::service('ln_datalayer.events')
          ->addEvent("form_validate_{$form['#id']}_{$key}", [
            'event' => 'review_main',
            'event_name' => 'review_submit_error',
            'review_rating' => 'Give it ' . intval($form_state->getValue('field_dsu_ratings')[0]['rating'] ?? 0 / 20) . "/5",
            'review_id' => $form_state->getValue('subject')[0]['value'],
            'content_id' => '',
            'content_name' => '',
            'form_name' => $form['#id'],
            'form_id' => $form['#id'],
            'error_code' => '403',
            'error_name' => $err,
            'module_name' => $info['name'],
            'module_version' => $info['version'],
          ]);
      }
    }
  }

  /**
   * Submit handler for comment form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitCommentForm(array &$form, FormStateInterface $form_state): void {
    // Redirect always to the first page.
    if (($url = $form_state->getRedirect())
      && $url instanceof Url) {
      $query = $url->getOption('query');
      if (isset($query['page'])) {
        unset($query['page']);
        $url = $url->setOption('query', $query);
        $form_state->setRedirectUrl($url);
      }
    }

    if (\Drupal::hasService('ln_datalayer.events')) {
      $info = \Drupal::service('extension.list.module')->getExtensionInfo('dsu_ratings_reviews');
      \Drupal::service('ln_datalayer.events')->addEvent("form_submit_{$form['#id']}", [
        'event' => 'review_main',
        'event_name' => 'review_submit',
        'review_rating' => 'Give it ' . intval($form_state->getValue('field_dsu_ratings')[0]['rating'] ?? 0 / 20) . "/5",
        'review_id' => $form_state->getValue('subject')[0]['value'],
        'content_id' => '',
        'content_name' => '',
        'recipe_id' => '',
        'recipe_name' => '',
        'item_id' => '',
        'item_name' => '',
        'form_name' => $form['#id'],
        'form_id' => $form['#id'],
        'module_name' => $info['name'],
        'module_version' => $info['version'],
      ]);
    }
  }
}
