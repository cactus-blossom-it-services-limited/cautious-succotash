/**
 * @file
 * Defines behaviors for the payment redirect form.
 */
(($, Drupal) => {
  /**
   * Attaches the commercePaymentRedirect behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commercePaymentRedirect behavior.
   */
  Drupal.behaviors.commercePaymentRedirect = {
    attach: (context) => {
      $('.payment-redirect-form', context).submit();
    },
  };
})(jQuery, Drupal);
