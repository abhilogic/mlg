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
  public function createBillingPlan($user_id = null, $card_detail = null) {
    try {
      $turn = 1;
      $user_controller = new UsersController();
      $response = $user_controller->getPaymentbrief(array('user_id' => $user_id));
      foreach ($response as $child) {
        $frequency = 'MONTH';
        $cycles = (strtoupper($child['plan_duration']) == 'QUATERLY') ? 3 : 1;
        if (strtoupper($child['plan_duration']) == 'YEARLY') {
          $frequency = 'YEAR';
        }

        $fixed_name = 'Deduction For  Subscription';
        $fixed_description = 'You will be charged ' . $child['package_amount'] . ' for ' . $child['plan_duration'];

        $fixed_payment_name = 'Regular payment defination';
        $fixed_payment_type = 'REGULAR';
        $fixed_payment_frequency = $frequency;
        $fixed_payment_frequency_interval = 1;

        $fixed_amount_value = $child['package_amount'];

        $fixed_amount_currency = 'USD';
        $fixed_cycles = $cycles;
        $fixed_charged_shipping_amount_value = 0;
        $fixed_charged_shipping_amount_currency = 'USD';
        $fixed_charged_tax_amount_value = 0;
        $fixed_charged_tax_amount_currency = 'USD';

        $trial_name = 'Subcription on trial';
        $trial_frequency = 'MONTH';
        $trial_frequency_interval = 1;
        $trial_amount_value = 0;
        $trial_amount_currency = 'USD';
        $trial_cycles = 1;
        $trial_charged_shipping_amount_value = 0;
        $trial_charged_shipping_amount_currency = 'USD';
        $trial_charged_tax_amount_value = 0;
        $trial_charged_tax_amount_currency = 'USD';

        $merchant_setup_fee_value = 0;
        $merchant_setup_fee_currency = 'USD';
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
            ),
            array(
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

        $user_controller = new UsersController();
        $access_token = $user_controller->paypalAccessToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/billing-plans/");
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
        $plan_id = $result['id'];
        $plan_status = $this->activatePayaplPlan($plan_id, $access_token);
        //      $billing_response = $this->billingAgreementViaCreditCard($user_id, $card_detail, $plan_id, $access_token);

        $user_purchase_items_table = TableRegistry::get('UserPurchaseItems');
        $order_date = '';
        $user_purchase_item = $user_purchase_items_table->find('all')
          ->select('order_date')
          ->where(['user_id' => $child['child_id']])
          ->orderDesc('id')->limit(1);
        foreach ($user_purchase_item as $item) {
          $item_order_date = (array)$item->order_date;
          $order_date = $item_order_date['date'];
        }
        $query = $user_purchase_items_table->query();
        //      $update_purchase_items =  $query->update()->set([
        //        'paypal_plan_id'=> $plan_id,
        //        'plan_status' => $plan_status,
        //        'billing_id' => $billing_response['id'],
        //        'billing_state' => $billing_response['state']
        //        ])->where(['user_id' => $child['child_id']])->execute();
        $update_purchase_items = $query->update()->set([
            'paypal_plan_id' => $plan_id,
            'paypal_plan_status' => $plan_status,
        ])->where(['user_id' => $child['child_id'], 'order_date' => $order_date])->execute();
      }
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
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

      curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/billing-plans/$plan_id/");
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
      $user_controller = new UsersController();
      if ($access_token == null) {
        $access_token = $user_controller->paypalAccessToken();
      }
      $user = $user_controller->getUserDetails($user_id, TRUE);

      $payment_method = 'credit_card';
      $payer_email = $user['email'];
      if ($card_details != null) {
        foreach ($card_details as $detail) {
          $card_number = $detail['number'];
          $expire_month = $detail['expire_month'];
          $expire_year = $detail['expire_year'];
          $cvv = $detail['cvv2'];
          $card_type = $detail['visa'];
        }
      }

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
                'type' => $card_type,
                'number' => $card_number,
                'expire_month' => $expire_month,
                'expire_year' => $expire_year,
                'cvv2' => $cvv,
              )
            )
          )
        )
      );

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/billing-agreements/");
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
    } catch (Exception $ex) {
      $this->log($ex->getMessage() . '(' . __METHOD__ . ')');
    }
    return json_decode($result);
  }

}