<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Controller\UsersController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Mailer\Email;
use Cake\Routing\Router;
use Cake\Datasource\ConnectionManager;

/**
 * PaymentController
 *
 * To Access all payment ways.
 */

class PaymentController extends AppController {
  
  public function initialize(){
    parent::initialize();
   // $conn = ConnectionManager::get('default');
    $this->loadComponent('RequestHandler');
     $this->RequestHandler->renderAs($this, 'json');
  }

  /**
   * Create Paypal Billing Plan.
   */
  public function createBillingPlan($user_id = null, $access_token = null, $trial_period = TRUE) {
    try {
        $user_controller = new UsersController();
        $response = $user_controller->getUserPurchaseDetails($user_id, TRUE);
        $frequency = 'MONTH';
        $cycles = (strtoupper($response['plan_duration']) == 'QUATERLY') ? 3 : 1;
        if (strtoupper($response['plan_duration']) == 'YEARLY') {
          $frequency = 'YEAR';
        }

        $fixed_name = 'Deduction For  Subscription';
        $fixed_description = 'You will be charged ' . $response['package_amount'] . ' for ' . $response['plan_duration'];

        $fixed_payment_name = 'Regular payment defination';
        $fixed_payment_type = 'REGULAR';
//        $fixed_payment_frequency = $frequency;

        //omit
        $fixed_payment_frequency = 'DAY';
        $fixed_payment_frequency_interval = 1;

        $fixed_amount_value = $response['package_amount'];

        $fixed_amount_currency = PAYPAL_CURRENCY;

//        $fixed_cycles = $cycles;

        //omit
        $fixed_cycles = 2;

        $fixed_charged_shipping_amount_value = 0;
        $fixed_charged_shipping_amount_currency = PAYPAL_CURRENCY;
        $fixed_charged_tax_amount_value = 0;
        $fixed_charged_tax_amount_currency = PAYPAL_CURRENCY;

        $trial_name = 'Subcription on trial';
        $trial_frequency = 'MONTH';
        $trial_frequency_interval = 1;
        $trial_amount_value = 0;
        $trial_amount_currency = PAYPAL_CURRENCY;
        $trial_cycles = 1;
        $trial_charged_shipping_amount_value = 0;
        $trial_charged_shipping_amount_currency = PAYPAL_CURRENCY;
        $trial_charged_tax_amount_value = 0;
        $trial_charged_tax_amount_currency = PAYPAL_CURRENCY;

        $merchant_setup_fee_value = 0;
        $merchant_setup_fee_currency = PAYPAL_CURRENCY;
        $return_url = 'http://www.paypal.com';
        $cancel_url = 'http://www.paypal.com/cancel';
        $auto_bill_amount = 'NO';
        $initial_fail_amount_action = 'CANCEL';
        $max_fail_attempts = 0;

        $post_fields = array(
          "name" => $fixed_name,
          "description" => $fixed_description,
          "type" => "FIXED",
          "payment_definitions" => array(
            array(
              "name" => $fixed_payment_name,
              "type" => $fixed_payment_type,
              "frequency" => $fixed_payment_frequency,
              "frequency_interval" => $fixed_payment_frequency_interval,
              "amount" => array(
                "value" => $fixed_amount_value,
                "currency" => $fixed_amount_currency
              ),
              "cycles" => $fixed_cycles,
              "charge_models" => array(
                array(
                  "type" => "SHIPPING",
                  "amount" => array(
                    "value" => $fixed_charged_shipping_amount_value,
                    "currency" => $fixed_charged_shipping_amount_currency
                  )
                ),
                array(
                  "type" => "TAX",
                  "amount" => array(
                    "value" => $fixed_charged_tax_amount_value,
                    "currency" => $fixed_charged_tax_amount_currency
                  )
                )
              )
            )
          ),
          "merchant_preferences" => array(
            "setup_fee" => array(
              "value" => $merchant_setup_fee_value,
              "currency" => $merchant_setup_fee_currency
            ),
            "return_url" => $return_url,
            "cancel_url" => $cancel_url,
            "auto_bill_amount" => $auto_bill_amount,
            "initial_fail_amount_action" => $initial_fail_amount_action,
            "max_fail_attempts" => $max_fail_attempts
          )
        );

        if ($trial_period == TRUE) {
          $trial_payment_definitions = array(
            "name" => $trial_name,
            "type" => "TRIAL",
            "frequency" => $trial_frequency,
            "frequency_interval" => $trial_frequency_interval,
            "amount" => array(
              "value" => $trial_amount_value,
              "currency" => $trial_amount_currency
            ),
            "cycles" => $trial_cycles,
            "charge_models" => array(
              array(
                "type" => "SHIPPING",
                "amount" => array(
                  "value" => $trial_charged_shipping_amount_value,
                  "currency" => $trial_charged_shipping_amount_currency
                )
              ),
              array(
                "type" => "TAX",
                "amount" => array(
                  "value" => $trial_charged_tax_amount_value,
                  "currency" => $trial_charged_tax_amount_currency
                )
              )
            )
          );
          $post_fields['payment_definitions'][] = $trial_payment_definitions;
        }
        $url = 'https://api.sandbox.paypal.com/v1/payments/billing-plans/';
        if (USE_SANDBOX_ACCOUNT == FALSE) {
          $url = 'https://api.paypal.com/v1/payments/billing-plans/';
        }
        if ($access_token == null) {
          $access_token = $user_controller->paypalAccessToken();
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: Bearer $access_token";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
          echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result, TRUE);
        $error = FALSE;
        $plan_id = isset($result['id']) ? $result['id'] : '';
        if (isset($result['name']) && ($result['name'] == 'VALIDATION_ERROR')) {
          $error = TRUE;
          throw new Exception('Exception occured: ' . json_encode($result));
        }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return array('plan_id' => $plan_id,
      'order_date' => $response['db_order_date'],
      'total_amount' => $response['package_amount'],
      'error' => $error);
  }

  /*
   * function to activate paypal plan
   *
   * activatePayaplPlan
   *
   * $plan_id is Plan Id to be activated
   */
  public function activatePayaplPlan($plan_id = null, $access_token = null) {
    try {
      $status = 'INACTIVE';
      $user_controller = new UsersController();
      if ($access_token == null) {
        $access_token = $user_controller->paypalAccessToken();
      }
      $post_fields = array(
        array(
          'op' => 'replace',
          'path' => '/',
          'value' => array(
            'state' => 'ACTIVE'
          )
        )
      );
      $ch = curl_init();

      $url = "https://api.sandbox.paypal.com/v1/payments/billing-plans/$plan_id/";
      if (USE_SANDBOX_ACCOUNT == FALSE) {
        $url = "https://api.paypal.com/v1/payments/billing-plans/$plan_id/";
      }
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");

      $headers = array();
      $headers[] = "Content-Type: application/json";
      $headers[] = "Authorization: Bearer $access_token";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
      }
      curl_close($ch);
      if ($httpCode == 200) {
        $status = 'ACTIVE';
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return $status;
  }

  /**
   * Create Paypal Billing Plan.
   *
   * Credit card details needed.
   */
  public function billingAgreementViaCreditCard($user_id = null, $card_details = null, $plan_id = null, $access_token = null) {
    try {
      $result = array();
      $user_controller = new UsersController();
      if ($access_token == null) {
        $access_token = $user_controller->paypalAccessToken();
      }
      $payer_email = '';
      $user_info = $user_controller->getUserDetails($user_id, TRUE);
      foreach ($user_info as $user) {
        $payer_email = $user['email'];
      }
      $payment_method = 'credit_card';

      $billing_address_exist = FALSE;
      $post_fields = array(
        'name' => 'Credit Card Payment',
        'description' => 'Recurring credit card payment',
        'start_date' => date('Y-m-d\TH:i:s\Z', strtotime('+1 day')),
        'plan' => array('id' => $plan_id),
        'payer' => array(
          'payment_method' => $payment_method,
          'payer_info' => array('email' => $payer_email),
          'funding_instruments' => array(
            array(
              'credit_card' => array(
                'first_name' => $card_details['first_name'],
                'last_name' => $card_details['last_name'],
                'type' => isset($card_details['card_type']) ? $card_details['card_type'] : 'visa',
                'number' => $card_details['number'],
                'expire_month' => $card_details['expire_month'],
                'expire_year' => $card_details['expire_year'],
                'cvv2' => $card_details['cvv2'],
                //omit
                'billing_address' => array (
                  'line1' => '065769 Holcomb Bridge Road #141',
                  'line2' => '5713 E Dimond Boulevard #B9',
                  'city' => 'Wichita',
                  'state' => 'KS',
                  'postal_code' => '67202',
                  'country_code' => 'US',
                  'phone' => '+1 6202311026',
                )
              )
            )
          )
        )
      );

      $url = "https://api.sandbox.paypal.com/v1/payments/billing-agreements/";
      if (USE_SANDBOX_ACCOUNT == FALSE) {
        $url = "https://api.paypal.com/v1/payments/billing-agreements/";
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
      curl_setopt($ch, CURLOPT_POST, 1);

      $headers = array();
      $headers[] = "Content-Type: application/json";
      $headers[] = "Authorization: Bearer $access_token";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      $result = json_decode($result, TRUE);
      if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
      }
      curl_close($ch);
      $error = FALSE;
      if (isset($result['name'])) {
        $error = TRUE;
        throw new Exception('Exception occured: ' . json_encode($result));
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return array('result' => $result, 'error' => $error);
  }

}