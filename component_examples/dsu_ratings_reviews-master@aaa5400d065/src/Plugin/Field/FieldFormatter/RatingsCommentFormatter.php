<?php

namespace Drupal\dsu_ratings_reviews\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * RatingsCommentFormatter for dsu comments formatter.
 *
 * @FieldFormatter(
 *   id = "dsu_ratings_reviews_comment_formatter",
 *   label = @Translation("DSU Ratings Reviews Comment list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class RatingsCommentFormatter extends CommentDefaultFormatter {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CommentDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request Stack.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match, EntityDisplayRepositoryInterface $entity_display_repository, RequestStack $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $entity_type_manager, $entity_form_builder, $route_match, $entity_display_repository);
    $this->requestStack = $request_stack;
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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('entity_display.repository'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get current route and compare with original call.
    // Comment/{comment} routes use internal redirect and we want to detect it.
    $content = parent::viewElements($items, $langcode);
    $route_name = $this->routeMatch->getRouteName();
    $master_request = $this->requestStack->getMainRequest();
    $original_route = NULL;
    if (!empty($master_request)) {
      $original_route = $master_request->get('_route');
    }

    // Comment/{comment} routes will only print 1 comment on our version.
    $display = DsuRatingsReviewsConstants::RATINGS_REVIEWS_VIEW_FULL_DISPLAY;
    $args = [$items->getEntity()->id()];
    if (!empty($original_route) && $original_route !== $route_name && $original_route === 'entity.comment.canonical') {
      $display = DsuRatingsReviewsConstants::RATINGS_REVIEWS_VIEW_SIMPLE_DISPLAY;
      $args[] = $master_request->get('comment')->id();
    }

    $view = Views::getView(DsuRatingsReviewsConstants::RATINGS_REVIEWS_VIEW);
    $comments = NULL;
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay($display);
      $view->preExecute();
      $view->execute();
      $comments = $view->buildRenderable(DsuRatingsReviewsConstants::RATINGS_REVIEWS_VIEW_FULL_DISPLAY, $args);
    }
    if (!empty($comments) && !empty($content[0]['comments'])) {
      $content[0]['comments'] = $comments;
    }
    return $content;
  }


}
