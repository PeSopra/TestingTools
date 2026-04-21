<?php

namespace Drupal\dsu_ratings_reviews;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\dsu_ratings_reviews\Services\CommentManager;

class DsuRatingsReviewsServiceProvider extends ServiceProviderBase{
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('comment.manager');
    $definition->setClass(CommentManager::class);
  }
}
