/**
 * @file
 *   JavaScript for the adding event tracking from advanced datalayer.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  window.dataLayer = window.dataLayer || [];

  const observer = new IntersectionObserver(function (entries, self) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        self.unobserve(entry.target);
        const $this = $(entry.target);
        const rat = $this.find('.dsu_rr-ratings_average').text();

        window.dataLayer.push({
          event: 'ratingReviewEvent',
          eventCategory: 'Ratings & Reviews',
          eventAction: 'Detail View',
          eventLabel: drupalSettings.ln_datalayer?.data?.content_name,
          reviewContent: drupalSettings.ln_datalayer?.data?.content_name,
        });
        //Datalayer GA4
        window.dataLayer.push({
          'event': 'review_main',
          'event_name': 'review_viewed',
          'review_rating': 'Give it ' + rat + '/5',
          'review_id': null,
          'content_id': drupalSettings.ln_datalayer?.data?.content_id,
          'content_name': drupalSettings.ln_datalayer?.data?.content_name,
          'item_id': drupalSettings.ln_datalayer?.data?.content_id,
          'module_name': drupalSettings.dsu_ratings_reviews.data.module_name,
          'module_version': drupalSettings.dsu_ratings_reviews.data.module_version,
        });
      }
    });
  }, { threshold: 0 });

  Drupal.behaviors.ln_tint_connector = {
    attach(context, settings) {

      once('dsu_rr_review_viewed', '.comments-type--dsu_ratings_reviews').forEach((rr_element) => {
        observer.observe(rr_element);
      });

      $(once('dsu_rr_review_recommended', '.comments-type--dsu_ratings_reviews input[name="field_dsu_recommend"]')).change(function (ev) {
        const $this = $(this);

        window.dataLayer.push({
          event: ($this.val() === '1') ? 'review_recommended' : 'review_not_recommended',
          eventCategory: 'Ratings & Reviews',
          eventAction: ($this.val() === '1') ? 'User Recommend - Yes' : 'User Recommend - No',
          eventLabel: drupalSettings.ln_datalayer?.data?.content_name,
          reviewContent: drupalSettings.ln_datalayer?.data?.content_name,
        });
        //Datalayer GA4
        window.dataLayer.push({
          'event': 'review_main',
          'event_name': ($this.val() === '1') ? 'review_recommended' : 'review_not_recommended',
          'review_rating': null,
          'review_id': null,
          'content_id': drupalSettings.ln_datalayer?.data?.content_id,
          'content_name': drupalSettings.ln_datalayer?.data?.content_name,
          'item_id': drupalSettings.ln_datalayer?.data?.content_id,
          'module_name': drupalSettings.dsu_ratings_reviews.data.module_name,
          'module_version': drupalSettings.dsu_ratings_reviews.data.module_version,
        });
      });

      $(once('dsu_rr_review_preview', '.comments-type--dsu_ratings_reviews #edit-preview')).click(function (ev) {
        const $this = $(this);
        const rating = $this.parents('form').find('[name="field_dsu_ratings[0][rating]"]').val();

        window.dataLayer.push({
          event: 'review_preview',
          eventCategory: 'Ratings & Reviews',
          eventAction: 'Preview Review Submission',
          eventLabel: drupalSettings.ln_datalayer?.data?.content_name,
          reviewContent: drupalSettings.ln_datalayer?.data?.content_name,
        });
        //Datalayer GA4
        window.dataLayer.push({
          'event': 'review_main',
          'event_name': 'review_preview',
          'review_rating': ((parseInt(rating) || 0) * 5 / 100), // Calculate rating stars.
          'review_id': null,
          'content_id': drupalSettings.ln_datalayer?.data?.content_id,
          'content_name': drupalSettings.ln_datalayer?.data?.content_name,
          'item_id': drupalSettings.ln_datalayer?.data?.content_id,
          'module_name': drupalSettings.dsu_ratings_reviews.data.module_name,
          'module_version': drupalSettings.dsu_ratings_reviews.data.module_version,
        });
      });

      $(once('dsu_rr_review_attach_media', '.comments-type--dsu_ratings_reviews button[name="ief-field_dsu_images-form-add"]')).mousedown(function (ev) {
        window.dataLayer.push({
          event: 'review_attach_media',
          eventCategory: 'Ratings & Reviews',
          eventAction: 'Attach Media to Review',
          eventLabel: drupalSettings.ln_datalayer?.data?.content_name,
          reviewContent: drupalSettings.ln_datalayer?.data?.content_name,
        });
        //Datalayer GA4
        window.dataLayer.push({
          'event': 'review_main',
          'event_name': 'review_attach_media',
          'review_rating': null,
          'review_id': null,
          'content_id': drupalSettings.ln_datalayer?.data?.content_id,
          'content_name': drupalSettings.ln_datalayer?.data?.content_name,
          'item_id': drupalSettings.ln_datalayer?.data?.content_id,
          'module_name': drupalSettings.dsu_ratings_reviews.data.module_name,
          'module_version': drupalSettings.dsu_ratings_reviews.data.module_version,
        });
      });

      $(once('review_attach_media_cancel', '.comments-type--dsu_ratings_reviews button[name="ief-add-cancel-field_dsu_images-form"]')).mousedown(function (ev) {
        window.dataLayer.push({
          event: 'review_attach_media',
          eventCategory: 'Ratings & Reviews',
          eventAction: 'Cancel Media Attachment',
          eventLabel: drupalSettings.ln_datalayer?.data?.content_name,
          reviewContent: drupalSettings.ln_datalayer?.data?.content_name,
        });
        //Datalayer GA4
        window.dataLayer.push({
          'event': 'review_main',
          'event_name': 'review_attach_media_cancel',
          'review_rating': null,
          'review_id': null,
          'content_id': drupalSettings.ln_datalayer?.data?.content_id,
          'content_name': drupalSettings.ln_datalayer?.data?.content_name,
          'item_id': drupalSettings.ln_datalayer?.data?.content_id,
          'module_name': drupalSettings.dsu_ratings_reviews.data.module_name,
          'module_version': drupalSettings.dsu_ratings_reviews.data.module_version,
        });
      });

      $(once('review_rating_selection', '.comments-type--dsu_ratings_reviews select[name="field_dsu_ratings[0][rating]"]')).change(function (ev) {
        const star_rate = ((parseInt($(this).val()) || 0) * 5 / 100); // Calculate rating stars.
        window.dataLayer.push({
          event: 'review_rating_selection',
          eventCategory: 'Ratings & Reviews',
          eventAction: 'Rating Selection - ' + star_rate,
          eventLabel: drupalSettings.ln_datalayer?.data?.content_name,
          reviewContent: drupalSettings.ln_datalayer?.data?.content_name,
          reviewRating: star_rate,
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
