<?php

namespace Drupal\dsu_ratings_reviews;


interface DsuRatingsReviewsConstants {

  const COMMENT_TYPE = 'dsu_ratings_reviews_comment_type';

  const RATINGS_FIELD = 'field_dsu_ratings';

  const RECOMMEND_FIELD = 'field_dsu_recommend';

  const TOS_FIELD = 'field_dsu_tos';

  const REPLY_PERMISSION = 'reply rating comments';

  const DISPLAY_NAME_FIELD = 'field_display_name';

  const MAIL_KEY = 'comment_created';

  const ROLE_MODERATOR = 'ln_moderator';

  const CONFIG_SUBJECT = 'mail_subject';

  const CONFIG_BODY = 'mail_body';

  const CONFIG_TOS = 'rating_tos';

  const CONFIG_ENABLE_MARKETING_OPTIN = 'rating_enable_marketing_optin';

  const CONFIG_MARKETING_OPTIN = 'rating_marketing_optin';

  const FIELD_GROUP_CONFIG = 'main_settings';

  const FIELD_GROUP_MAIL = 'mail';

  const FIELD_MARKETING_OPTIN = 'marketing';

  const REPLY_VIEW_MODE = 'reply';
  const RATINGS_REVIEWS_VIEW = 'dsu_ratings_node_view';
  const RATINGS_REVIEWS_VIEW_FULL_DISPLAY = 'block_ratings';
  const RATINGS_REVIEWS_VIEW_SIMPLE_DISPLAY = 'block_single_rating';
  const FLAG_ID_USEFUL = 'dsu_ratings_comment_useful';
  const FLAG_ID_UNUSEFUL = 'dsu_ratings_comment_unuseful';
  const RATTINGS_REVIEWS_FLAGS = [
    self::FLAG_ID_USEFUL,
    self::FLAG_ID_UNUSEFUL,
  ];
  const RATTINGS_REVIEWS_REVERSE_FLAGS = [
    self::FLAG_ID_USEFUL => self::FLAG_ID_UNUSEFUL,
    self::FLAG_ID_UNUSEFUL => self::FLAG_ID_USEFUL,
  ];
}
