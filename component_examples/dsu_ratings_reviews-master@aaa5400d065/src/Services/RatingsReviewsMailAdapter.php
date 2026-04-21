<?php

namespace Drupal\dsu_ratings_reviews\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;

/**
 * Class RatingsReviewsMailAdapter.
 */
class RatingsReviewsMailAdapter{

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
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Token Service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * RatingsReviewsAdaptations constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token Service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory, MessengerInterface $messenger, Token $token) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
    $this->token = $token;
  }

  /**
   * Implements hook_entity_insert().
   *
   * Sends an email to certain users when comments are to be moderated.
   */
  public function commentInsertSendMail(EntityInterface $entity) {
    $mail_config = $this->configFactory->get('dsu_ratings_reviews.settings');
    $subject = $mail_config->get(DsuRatingsReviewsConstants::CONFIG_SUBJECT);
    $body = $mail_config->get(DsuRatingsReviewsConstants::CONFIG_BODY);
    if (empty($subject)) {
      return;
    }
    $subject = $this->token->replace($subject, ['comment' => $entity]);
    $body = $this->token->replace($body, ['comment' => $entity]);

    $mails = [];
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['status' => 1, 'roles' => [DsuRatingsReviewsConstants::ROLE_MODERATOR]]);
    foreach ($users as $user){
      $mails[] = $user->getEmail();
    }

    $to = implode(',', $mails);
    if(!empty($to)){
      $params = [
        'body'    => $body,
        'subject' => $subject,
        'headers' => [
          'Bcc' => $to,
        ],
      ];
      /** @var \Drupal\Core\Mail\MailManagerInterface $mailManager */
      $mailManager = \Drupal::service('plugin.manager.mail');
      $result = $mailManager->mail('dsu_ratings_reviews', DsuRatingsReviewsConstants::MAIL_KEY, $to,
      $this->currentUser->getPreferredLangcode(), $params, NULL, TRUE);
      if ($result['result'] !== TRUE) {
        $this->messenger->addMessage($this->t('Moderator notification could not be sent after comment was created.'));
      }
    }
  }

  /**
   * Implements hook_mail().
   *
   * Sends an email to specific users to moderate comments when created.
   */
  public function sendMail($key, &$message, $params) {
    switch ($key) {
      case DsuRatingsReviewsConstants::MAIL_KEY:
        $site_config = $this->configFactory->get('system.site');
        $message['from'] = $site_config->get('mail');
        if (empty($message['to'])) {
          $message['to'] = $params['to'];
        }
        $message['subject'] = $params['subject'];
        $message['body'][] = $params['body'];
        break;
    }
  }

}
