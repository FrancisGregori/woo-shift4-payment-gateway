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
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {


            $this->id = 'shift4'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = __('Shift4 Gateway', 'woocommerce-gateway-shift4');
            $this->method_description = __('Shift4 works by adding payment fields on the checkout and then sending the details to Shift4 for verification.', 'woocommerce-gateway-shift4');

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
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

            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

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
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {


            $client = new Shift4API(null, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

            $this->testShift4CanPostSale($client->getAccessToken());


            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ($this->testmode) {
                    $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank">documentation</a>.';
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
                    <input id="shift4_ccNo" type="text" autocomplete="off">
                    </div>
                    <div class="form-row form-row-first">
                        <label>Expiry Date <span class="required">*</span></label>
                        <input id="shift4_expdate" type="text" autocomplete="off" placeholder="MM / YY">
                    </div>
                    <div class="form-row form-row-last">
                        <label>Card Code (CVC) <span class="required">*</span></label>
                        <input id="shift4_cvv" type="password" autocomplete="off" placeholder="CVC">
                    </div>
                    <div class="clear"></div>';

            do_action('woocommerce_credit_card_form_end', $this->id);

            echo '<div class="clear"></div></fieldset>';


        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
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

            // no reason to enqueue JavaScript if API keys are not set
            if (empty($this->authToken) || empty($this->clientGUID)) {
                return;
            }

            // do not work with card detailes without SSL unless your website is in a test mode
            if (!$this->sandbox && !is_ssl()) {
                return;
            }

            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            wp_enqueue_script('shift4_js', 'https://www.shift4payments.com/api/token.js');

            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script('woocommerce_shift4', plugins_url('shift4.js', __FILE__), array('jquery', 'shift4_js'));

            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script('woocommerce_shift4', 'shift4_params', array(
                'publishableKey' => $this->clientGUID
            ));

            wp_enqueue_script('woocommerce_shift4');

        }

        /*
          * Fields validation, more in Step 5
         */
        public function validate_fields()
        {

            // ...

        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */
        public function process_payment($order_id)
        {

            // ...

        }

        /*
         * In case you need a webhook, like PayPal IPN etc
         */
        public function webhook()
        {

            // ...

        }


        public function testShift4CanPostSale($token)
        {

            $randomInvoice = rand();

            /*$tokenizer = new \Woo_Shift4_Payment_Gateway\Api\Shift4Token($token, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

            $tokenizer->ip('173.49.87.94')
                ->expirationMonth(12)
                ->expirationYear(30)
                ->cardNumber('4321000000001119')
                ->cvv('333')
                ->cardType('VS')
                ->name('John Smith')
                ->zip('65000')
                ->address('65 Main Street')
                ->post();

            echo "<pre>";
            print_r($tokenizer->getToken());
            echo "</pre>";die;*/

            $transaction = new \Woo_Shift4_Payment_Gateway\Api\Shift4Transation($token, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

            $transaction->tax(11.14)
                ->total(111.45)
                ->clerk('1')
                ->invoiceNumber($randomInvoice)
                ->tokenValue($token)
                ->purchaseCard(array(
                    'customerReference' => 412348,
                    'destinationPostalCode' => 19134,
                    'productDescriptors' => array('rent')
                ))
                ->sale();

            $output = $transaction->output();

            echo "<pre>";
            print_r(json_encode(
                array(
                    'Request' => $transaction->request(),
                    'Output' => $output
                )
            ));
            echo "</pre>";
            die;
        }
    }

}