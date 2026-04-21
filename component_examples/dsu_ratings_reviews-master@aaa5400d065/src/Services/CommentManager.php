<?php

namespace Drupal\dsu_ratings_reviews\Services;

use Drupal\comment\CommentManager as CommentManagerParent;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Comment manager contains common functions to manage comment fields.
 */
class CommentManager extends CommentManagerParent {
  use StringTranslationTrait;


  /**
   * {@inheritdoc}
   */
  public function forbiddenMessage(EntityInterface $entity, $field_name) {
    if (!isset($this->authenticatedCanPostComments)) {
      // We only output a link if we are certain that users will get the
      // permission to post comments by logging in.
      $this->authenticatedCanPostComments = $this->entityTypeManager
        ->getStorage('user_role')
        ->load(RoleInterface::AUTHENTICATED_ID)
        ->hasPermission('post comments');
    }

    if ($this->authenticatedCanPostComments) {
      // We cannot use the redirect.destination service here because these links
      // sometimes appear on /node and taxonomy listing pages.
      if ($entity->get($field_name)->getFieldDefinition()->getSetting('form_location') == CommentItemInterface::FORM_SEPARATE_PAGE) {
        $comment_reply_parameters = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity' => $entity->id(),
          'field_name' => $field_name,
        ];
        $destination = ['destination' => Url::fromRoute('comment.reply', $comment_reply_parameters, ['fragment' => 'comment-form'])->toString()];
      }
      else {
        $destination = ['destination' => $entity->toUrl('canonical', ['fragment' => 'comment-form'])->toString()];
      }

      if ($this->userConfig->get('register') != UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
        // Users can register themselves.
        return $this->t('@login or @register to post comments', [
          '@login' => Link::fromTextAndUrl($this->t('Log in'), Url::fromRoute('user.login', [], ['query' => $destination]))->toString(),
          '@register' => Link::fromTextAndUrl($this->t('register'), Url::fromRoute('user.register', [], ['query' => $destination]))->toString(),
        ]);
      }
      else {
        // Only admins can add new users, no public registration.
        return $this->t('@login to post comments', [
          '@login' => Link::fromTextAndUrl($this->t('Log in'), Url::fromRoute('user.login', [], ['query' => $destination]))->toString(),
        ]);
      }
    }
    return '';
  }
}
