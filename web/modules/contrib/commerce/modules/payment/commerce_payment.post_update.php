<?php

/**
 * @file
 * Post update functions for the commerce_payment module.
 */

use Drupal\views\Views;

/**
 * Re-save payment methods to populate the payment_gateway_mode field.
 */
function commerce_payment_post_update_1(&$sandbox = NULL) {
  $payment_method_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_method');
  if (!isset($sandbox['current_count'])) {
    $query = $payment_method_storage->getQuery();
    $sandbox['total_count'] = $query->accessCheck(FALSE)->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $payment_method_storage->getQuery();
  $query->accessCheck(FALSE);
  $query->range($sandbox['current_count'], 25);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface[] $payment_methods */
  $payment_methods = $payment_method_storage->loadMultiple($result);
  foreach ($payment_methods as $payment_method) {
    $payment_method->save();
  }

  $sandbox['current_count'] += 25;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}

/**
 * Re-save payments to populate the payment_gateway_mode and completed fields.
 */
function commerce_payment_post_update_2(&$sandbox = NULL) {
  $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
  if (!isset($sandbox['current_count'])) {
    $query = $payment_storage->getQuery();
    $sandbox['total_count'] = $query->accessCheck(FALSE)->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $payment_storage->getQuery();
  $query->accessCheck(FALSE);
  $query->range($sandbox['current_count'], 25);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  // Renamed states.
  $state_map = [
    'capture_completed' => 'completed',
    'capture_partially_refunded' => 'partially_refunded',
    'capture_refunded' => 'refunded',
    'received' => 'completed',
  ];

  /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
  $payments = $payment_storage->loadMultiple($result);
  foreach ($payments as $payment) {
    // Update the state.
    $state = $payment->get('state')->value;
    if (isset($state_map[$state])) {
      $payment->set('state', $state_map[$state]);
    }
    // Migrate the 'test' field to 'payment_gateway_mode'.
    $payment_gateway = $payment->getPaymentGateway();
    if ($payment_gateway) {
      $supported_modes = $payment_gateway->getPlugin()->getSupportedModes();
      $mode = $payment->get('test')->value ? 'test' : 'live';
      $mode = in_array($mode, $supported_modes) ? $mode : $payment_gateway->getPlugin()->getMode();
      $payment->set('payment_gateway_mode', $mode);
    }
    // Migrate the 'captured' field to 'completed'.
    $payment->set('completed', $payment->get('captured')->value);
    $payment->save();
  }

  $sandbox['current_count'] += 25;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}

/**
 * Ensure each payment method has its own billing profile.
 */
function commerce_payment_post_update_3(&$sandbox = NULL) {
  $payment_method_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_method');
  if (!isset($sandbox['current_count'])) {
    $query = $payment_method_storage->getQuery();
    $query->accessCheck(FALSE);
    $query
      ->condition('reusable', TRUE)
      ->condition($query->orConditionGroup()
        ->condition('expires', \Drupal::time()->getRequestTime(), '>')
        ->condition('expires', 0))
      ->count();
    $sandbox['total_count'] = $query->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $payment_method_storage->getQuery();
  $query->accessCheck(FALSE);
  $query
    ->condition('reusable', TRUE)
    ->condition($query->orConditionGroup()
      ->condition('expires', \Drupal::time()->getRequestTime(), '>')
      ->condition('expires', 0))
    ->range($sandbox['current_count'], 50);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface[] $payment_methods */
  $payment_methods = $payment_method_storage->loadMultiple($result);
  foreach ($payment_methods as $payment_method) {
    if ($billing_profile = $payment_method->getBillingProfile()) {
      // Before Commerce 2.14 billing profiles were shared between
      // payment methods and orders. By ensuring that each payment method
      // has its own billing profile, we prevent accidental modifications,
      // where modifying the order's billing information changes the
      // payment method's billing information, or vice-versa.
      $new_billing_profile = $billing_profile->createDuplicate();
      $new_billing_profile->setOwnerId(0);
      $new_billing_profile->save();

      $payment_method->setBillingProfile($new_billing_profile);
      $payment_method->save();
    }
  }

  $sandbox['current_count'] += 50;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}

/**
 * Import the payments view.
 */
function commerce_payment_post_update_4() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->import([
    'views.view.commerce_order_payments',
  ]);
  return implode('<br>', $result->getFailed());
}

/**
 * Fix the "commerce_order_payments" view path.
 */
function commerce_payment_post_update_5() {
  $view = Views::getView('commerce_order_payments');
  if ($view) {
    $displays = $view->storage->get('display');
    $displays['page_1']['display_options']['path'] = 'admin/commerce/orders/%commerce_order/payments';
    $view->storage->set('display', $displays);
    $view->save();
  }
}
