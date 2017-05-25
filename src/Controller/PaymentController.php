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

  public function initialize() {
    parent::initialize();
    // $conn = ConnectionManager::get('default');
    $this->loadComponent('RequestHandler');
    $this->RequestHandler->renderAs($this, 'json');
  }

  /**
   * function paypalAcessToken
   *   To generate paypal Acess Token.
   */
  public function paypalAccessToken() {
    try {
      $url = "https://api.sandbox.paypal.com/v1/oauth2/token";
      $credential = PAYPAL_SANDBOX_CREDENTIAL;
      if (USE_SANDBOX_ACCOUNT == FALSE) {
        $url = "https://api.paypal.com/v1/oauth2/token";
        $credential = PAYPAL_LIVE_CREDENTIAL;
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_USERPWD, $credential);

      $headers = array();
      $headers[] = "Accept: application/json";
      $headers[] = "Accept-Language: en_US";
      $headers[] = "Content-Type: application/x-www-form-urlencoded";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
      }
      curl_close($ch);
      $result = json_decode($result, TRUE);
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return $result['access_token'];
  }

  /*
   * validatePaymentCardDetail()
   *
   * For validating Payment card details.
   */
  public function validatePaymentCardDetail($request_data, $access_token) {
    try {
      $response = $data = array();
      $message = '';
      $validation = array('data' => $data, 'response' => $response, 'message' => '');
      $data = $name = array();
      if (isset($request_data['line1']) && !empty($request_data['line1'])) {
        $data['billing_address']['line1'] = $request_data['line1'];
      } else {
        $message = "Address Line 1 is requred";
        throw new Exception($message);
      }
      if (isset($request_data['line2']) && !empty($request_data['line2'])) {
        $data['billing_address']['line2'] = $request_data['line2'];
      }
      if (isset($request_data['city']) && !empty($request_data['city'])) {
        $data['billing_address']['city'] = $request_data['city'];
      } else {
        $message = "city is requred in address";
        throw new Exception($message);
      }
      if (isset($request_data['state']) && !empty($request_data['state'])) {
        $data['billing_address']['state'] = $request_data['state'];
      } else {
        $message = "state is requred in address";
        throw new Exception($message);
      }
      if (isset($request_data['postal_code']) && !empty($request_data['postal_code'])) {
        $data['billing_address']['postal_code'] = $request_data['postal_code'];
      } else {
        $message = "postal code is requred in address";
        throw new Exception($message);
      }
      if (isset($request_data['country_code']) && !empty($request_data['country_code'])) {
        $data['billing_address']['country_code'] = $request_data['country_code'];
      } else {
        $message = "country code is requred in address";
        throw new Exception($message);
      }
      if (isset($request_data['phone']) && !empty($request_data['phone'])) {
        $data['billing_address']['phone'] = $request_data['phone'];
      } else {
        $message = "phone is requred in address";
        throw new Exception($message);
      }
      if (isset($request_data['name']) && !empty($request_data['name'])) {
        $data['first_name'] = $request_data['name'];
        $name = explode(' ', $request_data['name']);
      } else {
        $message = 'Name can not be blank';
        throw new Exception($message);
      }
      if (count($name) >= 2) {
        $data['first_name'] = @current($name);
        $data['last_name'] = @end($name);
      }
      if (isset($request_data['card_number']) && !empty($request_data['card_number'])) {
        $data['number'] = trim($request_data['card_number']);
      } else {
        $message = 'Card Number is required';
        throw new Exception($message);
      }
      if (isset($request_data['expiry_month']) && !empty($request_data['expiry_month'])) {
        $data['expire_month'] = $request_data['expiry_month'];
      } else {
        $message = 'Expiry Month is required';
        throw new Exception($message);
      }
      if (isset($request_data['expiry_year']) && !empty($request_data['expiry_year'])) {
        $data['expire_year'] = $request_data['expiry_year'];
      } else {
        $message = 'Expiry Year is required';
        throw new Exception($message);
      }
      if (isset($request_data['cvv']) && !empty($request_data['cvv'])) {
        if (strlen($request_data['cvv']) > 4) {
          $message = 'not valid CVV';
          throw new Exception($message);
        }
        $data['cvv2'] = $request_data['cvv'];
      } else {
        $message = 'CVV is required';
        throw new Exception($message);
      }
      $data['type'] = isset($request_data['card_type']) && !empty($request_data['card_type']) ?
        $request_data['card_type'] : 'visa';
      $data['external_customer_id'] = 'customer' . '_' . time();

      $url = "https://api.sandbox.paypal.com/v1/vault/credit-cards/";
      if (USE_SANDBOX_ACCOUNT == FALSE) {
        $url = "https://api.paypal.com/v1/vault/credit-cards/";
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_POST, 1);
      $headers = array();
      $headers[] = "Content-Type: application/json";
      $headers[] = "Authorization: Bearer $access_token";
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if (curl_errno($ch)) {
        $message = 'Some error occured';
        throw new Exception(curl_error($ch));
      }
      curl_close($ch);
      switch ($httpCode) {
        case 401 : $message = 'Some error occured. Unable to proceed, Kindly contact to administrator';
          throw new Exception('Unauthorised Access');
          break;

        case 500 : $message = 'Some error occured. Unable to proceed, Kindly contact to administrator';
          throw new Exception('Internal Server Error Occured');
          break;
      }
      $response = json_decode($response, TRUE);
      if (isset($response['name']) && $response['name'] == 'VALIDATION_ERROR') {
        foreach ($response['details'] as $error_details) {
          $error_fields = explode(',', $error_details['field']);
          foreach ($error_fields as $error_field) {
            switch($error_field) {
              case 'number' :  $message = "Card number is not valid \n";
               break;
              case 'billing_address.country_code' :
                $message = !empty($error_details['issue']) ? $error_details['issue'] : 'Country code is not valid'; 
               break;
            }
          }
        }
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    $validation['data'] = $data;
    $validation['message'] = $message;
    $validation['response'] = $response;
    return $validation;
  }

  /**
   * Create Paypal Billing Plan.
   */
  public function createBillingPlan($user_id = null, $access_token = null, $trial_period = TRUE) {
    try {
      $user_controller = new UsersController();
      $response = $user_controller->getUserPurchaseDetails($user_id, TRUE, TRUE);
      $frequency = 'MONTH';
      $cycles = (strtoupper($response['plan_duration']) == 'QUATERLY') ? 3 : 1;
      if (strtoupper($response['plan_duration']) == 'YEARLY') {
        $frequency = 'YEAR';
      }

      $fixed_name = 'Deduction For  Subscription';
      $fixed_description = 'You will be charged ' . $response['package_amount'] . ' for ' . $response['plan_duration'];

      $fixed_payment_name = 'Regular payment defination';
      $fixed_payment_type = 'REGULAR';
      //$fixed_payment_frequency = $frequency;
      //omit
      $fixed_payment_frequency = 'DAY';
      $fixed_payment_frequency_interval = 1;

      $fixed_amount_value = $response['package_amount'];

      $fixed_amount_currency = PAYPAL_CURRENCY;

//      $fixed_cycles = $cycles;
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

      $merchant_setup_fee_value = ($trial_period == TRUE) ? 0 : $fixed_amount_value;
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
        $access_token = $this->paypalAccessToken();
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
      'order_timestamp' => $response['order_timestamp'],
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
        $access_token = $this->paypalAccessToken();
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
        $access_token = $this->paypalAccessToken();
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
                'last_name' => isset($card_details['last_name']) ? $card_details['last_name'] : $card_details['first_name'],
                'type' => isset($card_details['card_type']) ? $card_details['card_type'] : 'visa',
                'number' => $card_details['number'],
                'expire_month' => $card_details['expire_month'],
                'expire_year' => $card_details['expire_year'],
                'cvv2' => $card_details['cvv2'],
                'billing_address' => $card_details['billing_address']
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
