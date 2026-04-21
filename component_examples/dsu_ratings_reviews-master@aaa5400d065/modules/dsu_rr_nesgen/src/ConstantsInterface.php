<?php

namespace Drupal\dsu_rr_nesgen;

/**
 * Constants file for dsu_rr_nesgen module.
 */
interface ConstantsInterface {
  public const SENTIMENT_POSITIVE = 'positive';
  public const SENTIMENT_NEUTRAL = 'neutral';
  public const SENTIMENT_NEGATIVE = 'negative';

  public const INITIAL_PROMP = "Analyze the user's input and return a JSON object with keys sentiment (strictly 'positive', 'neutral', or 'negative', no numbers), response (summary/insight based on sentiment), and response_to_user (tailored reply based on sentiment); default to 'neutral' if sentiment is unclear, and ensure valid JSON formatting";

}
