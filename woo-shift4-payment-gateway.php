<?php

/*
 * Plugin Name: WooCommerce Shift4 Payment Gateway
 * Description: Take credit card payments on your store using Shift4.
 * Author: Francis Gregori Munis
 * Author URI: https://www.francisgregori.com.br
 * Version: 1.0.0
 * Text Domain: woocommerce-gateway-shift4
 * Domain Path: /languages
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define SHITF4_PLUGIN_FILE.
if (!defined('SHITF4_PLUGIN_FILE')) {
    define('SHITF4_PLUGIN_FILE', __FILE__);
}


include_once 'api/autoload.php';
include_once 'vendor/autoload.php';

use Woo_Shift4_Payment_Gateway\Api\Shift4API;

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'shift4_add_gateway_class');
function shift4_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Shift4_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'woocommerce_shift4_gateway_init');

function woocommerce_shift4_gateway_init()
{


    class WC_Shift4_Gateway extends WC_Payment_Gateway
    {

        /**
         * @var bool
         */
        protected $sandbox;

        /**
         * @var string
         */
        protected $apiUrl;

        /**
         * @var string
         */
        protected $companyName;

        /**
         * @var string
         */
        protected $interfaceName;

        /**
         * @var string
         */
        protected $clientGUID;

        /**
         * @var string
         */
        protected $authToken;

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {

            if (!session_id()) {
                session_start();
            }

            $this->id = 'shift4'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = __('Shift4 Gateway', 'woocommerce-gateway-shift4');
            $this->method_description = __('Shift4 works by adding payment fields on the checkout and then sending the details to Shift4 for verification.', 'woocommerce-gateway-shift4');

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products',
                'refunds'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->sandbox = 'yes' === $this->get_option('sandbox');
            $this->apiUrl = $this->get_option('api_url');
            $this->companyName = $this->get_option('company_name');
            $this->interfaceName = $this->get_option('interface_name');

            $this->clientGUID = $this->sandbox ? $this->get_option('sandbox_client_guid') : $this->get_option('production_client_guid');
            $this->authToken = $this->sandbox ? $this->get_option('sandbox_auth_token') : $this->get_option('production_auth_token');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Custom JavaScript
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Shift4 Gateway',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Credit Card',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Pay with your credit card via our super-cool payment gateway.',
                ),
                'sandbox' => array(
                    'title' => 'Sandobox mode',
                    'label' => 'Enable sandbox mode',
                    'type' => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default' => 'yes',
                    'desc_tip' => true,
                ),
                'api_url' => array(
                    'title' => 'API URL',
                    'type' => 'text'
                ),
                'company_name' => array(
                    'title' => 'Company name',
                    'type' => 'text'
                ),
                'interface_name' => array(
                    'title' => 'Interface name',
                    'type' => 'text'
                ),
                'production_client_guid' => array(
                    'title' => 'ClientGUID(Production)',
                    'type' => 'text'
                ),
                'production_auth_token' => array(
                    'title' => 'AuthToken(Production)',
                    'type' => 'password'
                ),
                'sandbox_client_guid' => array(
                    'title' => 'ClientGUID(Sandbox)',
                    'type' => 'text'
                ),
                'sandbox_auth_token' => array(
                    'title' => 'AuthToken(Sandbox)',
                    'type' => 'password',
                )
            );

        }

        /**
         * Insert the plugins scripts
         */
        public function payment_scripts()
        {

            // we need JavaScript to process a token only on cart/checkout pages, right?
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            // do not work with card detailes without SSL unless your website is in a test mode
            if (!$this->sandbox && !is_ssl()) {
                return;
            }

            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            wp_enqueue_script('jquery.mask.min', plugin_dir_url(__FILE__) . 'assets/js/jquery.mask.min.js', dirname(__FILE__), array('jquery'));

        }


        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {

            if (!$_SESSION['accessToken']) {
                $client = new Shift4API(null, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

                $_SESSION['accessToken'] = $client->getAccessToken();
            }

            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ($this->sandbox) {
                    $this->description .= ' TEST MODE ENABLED.';
                    $this->description = trim($this->description);
                }
                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

            // Add this action hook if you want your custom gateway to support it
            do_action('woocommerce_credit_card_form_start', $this->id);

            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
            echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
                    <input id="shift4_ccNo" type="text" autocomplete="off" name="shift4_ccNo">
                    </div>
                    <div class="form-row form-row-first">
                        <label>Expiry Date <span class="required">*</span></label>
                        <input id="shift4_expdate" type="text" autocomplete="off" placeholder="MM/YY" name="shift4_expdate">
                    </div>
                    <div class="form-row form-row-last">
                        <label>Card Code (CVC) <span class="required">*</span></label>
                        <input id="shift4_cvc" type="text" autocomplete="off" placeholder="CVC" name="shift4_cvc">
                    </div>
                    <div class="clear"></div>';

            do_action('woocommerce_credit_card_form_end', $this->id);

            echo '<div class="clear"></div></fieldset>';

            echo '<script>
                    jQuery(function ($) {$("#shift4_ccNo").mask("9999 9999 9999 9999");})
                    jQuery(function ($) {$("#shift4_expdate").mask("99/99");})
                    jQuery(function ($) {$("#shift4_cvc").mask("9999");})
                  </script>';


        }

        /*
          * Fields validation, more in Step 5
         */
        public function validate_fields()
        {

            $errors = array();

            if (empty($_POST['shift4_ccNo'])) {
                wc_add_notice(__('Credit card number is required!', 'woocommerce-gateway-shift4'), 'error');
                $errors[] = 'shift4_ccNo';
            }

            if (empty($_POST['shift4_expdate'])) {
                wc_add_notice(__('Expiration date is required!', 'woocommerce-gateway-shift4'), 'error');
                $errors[] = 'shift4_expdate';
            }

            if (empty($_POST['shift4_cvc'])) {
                wc_add_notice(__('CVV is required!', 'woocommerce-gateway-shift4'), 'error');
                $errors[] = 'shift4_cvc';
            }

            if (count($errors) > 0) {
                return false;
            }

            return true;

        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */
        public function process_payment($order_id)
        {

            global $woocommerce;

            $order = wc_get_order($order_id);

            $invoiceNumber = str_pad($order_id, 10, '0', STR_PAD_LEFT);

            $transaction_products = array();

            $order_items = $order->get_items();

            if (!is_wp_error($order_items)) {
                foreach ($order_items as $item_id => $order_item) {
                    $transaction_products[] = $order_item->get_name();
                }
            }


            try {

                $taxes = 0;

                if (is_array(WC()->cart->get_taxes()) && count(WC()->cart->get_taxes()) > 0) {
                    foreach (WC()->cart->get_taxes() as $tax) {
                        $taxes += $tax;
                    }
                }


                $tokenizer = new \Woo_Shift4_Payment_Gateway\Api\Shift4Token($_SESSION['accessToken'], $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

                $tokenizer
                    ->setTax($taxes)
                    ->setCustomerReference($order->get_user_id() ?: $_POST['billing_email'])
                    ->setTransactionProducts($transaction_products)
                    ->setExpirationDate($_POST['shift4_expdate'])
                    ->setInvoceNumber($invoiceNumber)
                    ->setCardNumber($_POST['shift4_ccNo'])
                    ->setTotal($order->get_total())
                    ->setCvv($_POST['shift4_cvc'])
                    ->setName($_POST['billing_first_name'])
                    ->setLastName($_POST['billing_last_name'])
                    ->setPostalCode($_POST['billing_postcode'])
                    ->setAddress($_POST['billing_address_1'] . (!empty($_POST['billing_address_2']) ? ", {$_POST['billing_address_2']}" : ''))
                    ->post();


                if ($tokenizer->authorizedTransaction()) {

                    $transaction = new \Woo_Shift4_Payment_Gateway\Api\Shift4Transaction($_SESSION['accessToken'], $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

                    $transaction
                        ->setTax($taxes)
                        ->setTotal($order->get_total())
                        ->setInvoice($invoiceNumber)
                        ->setCardToken($tokenizer->getCardToken())
                        ->capture();

                    $output = $transaction->getOutput();


                    if ($output['result'][0]['transaction']['responseCode'] === 'A') {

                        try {

                            // we received the payment
                            $order->payment_complete();
                            $order->reduce_order_stock();

                            $order->add_order_note("Shift4 charge complete (Charge ID: {$output['result'][0]['transaction']['invoice']} )", true);

                            // Empty cart
                            $woocommerce->cart->empty_cart();

                            // Redirect to the thank you page
                            return array(
                                'result' => 'success',
                                'redirect' => $this->get_return_url($order)
                            );
                        } catch (Exception $e) {

                            wc_add_notice(__('Check the information and try again.', 'woocommerce-gateway-shift4'), 'error');
                            return;
                        }

                    } else {
                        wc_add_notice(__('Check the information and try again.', 'woocommerce-gateway-shift4'), 'error');
                        return;
                    }

                } else {
                    wc_add_notice(__('Check the information and try again.', 'woocommerce-gateway-shift4'), 'error');
                    return;
                }
            } catch (Exception $e) {
                wc_add_notice(__('Check the information and try again.', 'woocommerce-gateway-shift4'), 'error');
                return;
            }
        }

        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = wc_get_order($order_id);

            $invoiceNumber = str_pad($order_id, 10, '0', STR_PAD_LEFT);

            $client = new Shift4API(null, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

            $transaction = new \Woo_Shift4_Payment_Gateway\Api\Shift4Transaction($client->getAccessToken(), $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

            $transaction->setInvoice($invoiceNumber)
                ->setTotal($amount)
                ->setNote($reason)
                ->refund();

            $output = $transaction->getOutput();


            if ($output['result'][0]['transaction']['responseCode'] === 'A') {

                $order->add_order_note("Shift4 refund complete.", true);
                return true;
            }

            return false;

        }
    }
}