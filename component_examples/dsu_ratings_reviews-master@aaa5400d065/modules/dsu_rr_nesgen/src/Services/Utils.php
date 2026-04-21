<?php

namespace Drupal\dsu_rr_nesgen\Services;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\comment\CommentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\comment\CommentStorageInterface;
use Drupal\dsu_core\Services\NoticeInterface;
use Drupal\dsu_ratings_reviews\DsuRatingsReviewsConstants;
use Drupal\dsu_rr_nesgen\ConstantsInterface;
use Drupal\ln_nesgen\Services\NesGenClientInterface;
use Drupal\user\UserStorageInterface;

/**
 * Class for Utils service.
 */
class Utils implements UtilsInterface {
  use StringTranslationTrait;

  /**
   * The dsu_rr_nesgen.settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * The commment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected CommentStorageInterface $commentStorage;

  /**
   * Constructs a new Utils object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Parameter $config_factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\dsu_core\Services\NoticeInterface $notice
   *   The notice service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\ln_nesgen\Services\NesGenClientInterface $nesGenClient
   *   The NesGen service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected LoggerChannelInterface $logger,
    EntityTypeManagerInterface $entityTypeManager,
    protected NoticeInterface $notice,
    protected TimeInterface $time,
    protected NesGenClientInterface $nesGenClient,
  ) {
    $this->config = $config_factory->get('dsu_rr_nesgen.settings');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->commentStorage = $entityTypeManager->getStorage('comment');
  }

  /**
   * {@inheritdoc}
   */
  public function analizeReview(CommentInterface $entity): void {
    try {
      $chat = [];
      if ($prepromt = $this->config->get('nesgen.prepromt')) {
        $chat[] = new ChatMessage('system', $prepromt);
      }
      $chat[] = new ChatMessage('system', ConstantsInterface::INITIAL_PROMP);
      $chat[] = new ChatMessage('user', $entity->get('field_dsu_comment')->value);
      $mesage = $this->nesGenClient->chat(new ChatInput($chat));

      // Check if auto-reply is enabled based on review sentiment.
      if (
        ($text = $mesage->getText())
        && ($response = Json::decode($text))
        && isset($response['sentiment'])
        && isset($response['response_to_user'])
        && !empty($this->config->get('nesgen.sentiment')[$response['sentiment']])
      ) {
        $this->createChildComment($entity, $response['response_to_user']);
      }
    }
    catch (\Throwable $e) {
      $this->notice->sendNotice(
        $this->t('An error occurred while sending the review to NesGen: @error_message', [
          '@error_message' => $e->getMessage(),
        ]),
        Url::fromRoute('dsu_rr_nesgen.settings_form'),
      );
    }
  }

  /**
   * Create a review reply.
   *
   * @param \Drupal\comment\CommentInterface $review
   *   The review entity.
   * @param string $reply_text
   *   The reply text received by NesGen.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createChildComment(CommentInterface $review, string $reply_text): void {
    /** @var \Drupal\user\UserInterface $author */
    $author = NULL;
    if ($uid = $this->config->get('reply.author')) {
      $author = $this->userStorage->load($uid);
    }
    $reply = $this->commentStorage->create([
      'entity_type' => $review->get('entity_type')->value,
      'entity_id' => $review->get('entity_id')?->entity->id(),
      'field_name' => $review->get('field_name')->value,
      'uid' => $author?->id() ?? 0,
      'mail' => $author?->getEmail(),
      'langcode' => $review->get('langcode')->value,
      'pid' => $review->id(),
      'comment_type' => DsuRatingsReviewsConstants::COMMENT_TYPE,
      'field_display_name' => $this->config->get('reply.author_name') ?? $author?->getDisplayName(),
      'subject' => $this->config->get('reply.subject'),
      'field_dsu_comment' => $reply_text,
      'created' => $this->time->getCurrentTime(),
      'status' => TRUE,
    ]);

    $reply->save();

    if (!$review->isPublished()) {
      $review->setPublished();
      $review->save();
    }
  }

}
