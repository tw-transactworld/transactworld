<?php
/*
  Plugin Name: TransactWorld Gateway
  Plugin URI: http://docs.transactworld.com/integration/third-party-plugins.php
  Description: TransactWorld Gateway.
  Version: 1.1
  Author: Paymentz
  Author URI: http://docs.paymentz.com/integration/third-party-plugins.php
  
  Copyright: © 2009-2020 TransactWorld.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH'))
    exit;
add_action('plugins_loaded', 'woocommerce_pz_paymentz_init', 0);

function woocommerce_pz_paymentz_init() {
    if (!class_exists('WC_Payment_Gateway'))
        return;

    /**
     * Gateway class
     */
    class WC_PZ_Paymentz extends WC_Payment_Gateway {

        public function __construct() {
            // Go wild in here
            $this->id = 'paymentz';
            $this->method_title = __('paymentz', 'pz');
            $this->icon = plugins_url('images/transactworld.png', __FILE__);
            $this->has_fields = false;
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->merchant_id = $this->settings['merchant_id'];
            $this->working_key = $this->settings['working_key'];
            //$this->access_code = $this->settings['access_code'];

            //Gateway specific fields start
            $this->totype = $this->settings['totype'];
			$this->partenerid = $this->settings['partenerid'];
			
			$this->ipaddr = $this->settings['ipaddr'];
            $this->livemode = $this->settings['livemode'];
            $this->liveurl = $this->settings['liveurl'];
            $this->testurl = $this->settings['testurl'];
			$this->paymenttype = "";
            $this->cardtype = "";
            $this->reservedField1 = "";
            $this->reservedField2 = "";
            $this->url = '';
            //Gateway specific fields end

            $this->notify_url = str_replace('https:', 'http:', home_url('index.php/checkout/wc-api/WC_PZ_Paymentz'));
            $this->msg['message'] = "";
            $this->msg['class'] = "";

            //update for woocommerce >2.0
            add_action('woocommerce_api_wc_pz_paymentz', array($this, 'check_paymentz_response'));
            add_action('valid-paymentz-request', array($this, 'successful_request'));
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }
            add_action('woocommerce_receipt_paymentz', array($this, 'receipt_page'));
            add_action('woocommerce_thankyou_paymentz', array($this, 'thankyou_page'));
        }

        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'pz'),
                    'type' => 'checkbox',
                    'label' => __('Enable TransactWorld Payment Module.', 'pz'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'pz'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'pz'),
                    'default' => __('TransactWorld', 'pz')),
                'description' => array(
                    'title' => __('Description:', 'pz'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'pz'),
                    'default' => __('Pay securely by Credit or internet banking through TransactWorld Secure Servers.', 'pz')),
                'merchant_id' => array(
                    'title' => __('Merchant ID', 'pz'),
                    'type' => 'text',
                    'description' => __('This id(USER ID) available at "Generate Working Key" of "Settings and Options at TransactWorld."')),
                'working_key' => array(
                    'title' => __('Working Key', 'pz'),
                    'type' => 'text',
                    'description' => __('Given to Merchant by TransactWorld', 'pz'),
                ),
                'totype' => array(
                    'title' => __('Partner Name', 'pz'),
                    'type' => 'text',
                    'description' => __('Processor Name', 'pz'),
                ),
				
				
				
				'partenerid' => array(
                    'title' => __('Partner Id', 'pz'),
                    'type' => 'text',
                    'description' => __('Enter Partner Id', 'pz'),
                ),
				
				
				'ipaddr' => array(
                    'title' => __('Ip Address', 'pz'),
                    'type' => 'text',
                    'description' => __('Enter your ip address', 'pz'),
                ),
				
				
                'livemode' => array(
					'title' 			=> __('Live Mode Activation', 'pz'),
					'type' 			=> 'select',
					'options' 		=> array('N'=>'N','Y'=>'Y'),
					'description' => __('Live Mode Activation', 'pz')
				),
                'liveurl' => array(
                    'title' => __('Live Mode URL', 'pz'),
                    'type' => 'text',
                    'description' => __('Live Mode Transaction URL', 'pz'),
                ),
                'testurl' => array(
                    'title' => __('Test Mode URL', 'pz'),
                    'type' => 'text',
                    'description' => __('Test Mode Transaction URL', 'pz'),
                )
            );
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         * */
        public function admin_options() {
            echo '<h3>' . __('TransactWorld Payment Gateway', 'pz') . '</h3>';
            echo '<p>' . __('TransactWorld is most popular payment gateway for online shopping') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }

        /**
         *  There are no payment fields for paymentz, but we want to show the description if set.
         * */
        function payment_fields() {
            if ($this->description)
                echo wpautop(wptexturize($this->description));
        }

        /**
         * Receipt Page
         * */
        function receipt_page($order) {

            echo '<p>' . __('Thank you for your order, please click the button below to pay with TransactWorld.', 'pz') . '</p>';
            echo $this->generate_paymentz_form($order);
        }

        /*         * * Thankyou Page* */

        function thankyou_page($order) {
            if (!empty($this->instructions))
                echo wpautop(wptexturize($this->instructions));
        }

        /**
         * Process the payment and return the result
         * */
        function process_payment($order_id) 
        {
            $order = new WC_Order($order_id);            
            $order->update_status('pending',__('Awaiting offline payment', 'wc-gateway-offline'));
            $description=$order_id.'-'.$this->merchant_id;
            global $wpdb;
            $table_name = $wpdb->prefix . "paymentz_tbl";
            $sql = $wpdb->insert('' . $table_name . '', array('woocommerce_id' =>$order_id,'order_id' =>$description,'order_status' => 'authstarted' ));                                   
            $order->reduce_order_stock();
            WC()->cart->empty_cart();
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url($order));
        }

        /**
         * Check for valid paymentz server callback
         * */
        function check_paymentz_response() {
            global $woocommerce;
            $msg['class'] = 'error';
            $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

            if (isset($_REQUEST['status'])) {
                $trackingid = $_REQUEST['trackingid'];
                $order_id = $_REQUEST["desc"];
                $amount = $_REQUEST["amount"];
                $descriptor = $_REQUEST["descriptor"];
                $checksum = $_REQUEST["checksum"];
                if ($order_id != '') {
                    try {
                        
                        list($first, $last) = explode("-", $order_id);
                        $order = new WC_Order($first);
                        $order_status = $_REQUEST['status'];
                        $transauthorised = false;
                        if (order_status != '') {
                            if ($order_status == "Y") {
                                $transauthorised = true;
                                $msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $msg['class'] = 'success';
                                
                                    $order->update_status('processing', __('Payment successful'));
                                    $order->add_order_note('TransactWorld payment successful.');                                     
                                    global $wpdb;
                                    $wpdb->update('wp_paymentz_tbl', array('order_status' => 'capturesuccess','tracking_id'=>$trackingid), array('woocommerce_id' => $first));
                                    $woocommerce->cart->empty_cart();
                                
                            } else if ($order_status === "P") {
                                $msg['message'] = "Thank you for shopping with us. We will keep you posted regarding the status of your order through e-mail. <span style='color:red'>However your payment confirmation is pending</span>";
                                $msg['class'] = 'success';
                                $order->update_status('pending', __('TransactWorld payment pending'));
                                global $wpdb;
                                $wpdb->update('wp_paymentz_tbl', array('order_status' => 'authstarted','tracking_id'=>$trackingid), array('woocommerce_id' => $first));
                                $woocommerce->cart->empty_cart();
                            } else if ($order_status === "N") {
                                $msg['class'] = 'error';
                                $msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed.";
                                $order->update_status('failed', __('TransactWorld payment Delined'));
                                global $wpdb;
                                $wpdb->update('wp_paymentz_tbl', array('order_status' => 'authfailed','tracking_id'=>$trackingid), array('woocommerce_id' => $first));
                                $woocommerce->cart->empty_cart();
                            } else {
                                $msg['class'] = 'error';
                                $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                            }
                        }
                    } catch (Exception $e) {

                        $msg['class'] = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                    }
                }
            }

            if (function_exists('wc_add_notice')) {
                wc_add_notice($msg['message'], $msg['class']);
            } else {
                if ($msg['class'] == 'success') {
                    $woocommerce->add_message($msg['message']);
                } else {
                    $woocommerce->add_error($msg['message']);
                }
                $woocommerce->set_messages();
            }
            //$redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));
            $redirect_url = $this->get_return_url($order);
            wp_redirect($redirect_url);
            exit;
        }

        /*
          //Removed For WooCommerce 2.0
          function showMessage($content){
          return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
          } */

        /**
         * Generate paymentz button link
         * */
        public function generate_paymentz_form($order_id) {
            global $woocommerce;
            $order = new WC_Order($order_id);
            $order_id = $order_id . '-' .$this->merchant_id;
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                //check ip from share internet
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                //to check ip is pass from proxy
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            //var_dump("merchant_id".get_locale());
            //Hardcoded datas pz update it...
            $ip = "127.0.0.1";
            $redirecturl = $this->notify_url;
            $country = $woocommerce->customer->get_country();
            $CURRENCY = get_woocommerce_currency();
            $checksum = MD5(trim($this->merchant_id) . "|" . trim($this->totype) . "|" . trim($order->order_total) . "|" . trim($order_id) . "|" . trim($redirecturl) . "|" . trim($this->working_key));
            if ('Y' == $this->livemode) {
               // $this->url ="https://". $this->liveurl."/transaction/PayProcessController";
                $this->url =$this->liveurl;
            } else {
               // $this->url ="https://". $this->testurl."/transaction/PayProcessController";
                $this->url =$this->testurl;
            }

            $paymentz_args = array(
                'toid' => $this->merchant_id,
				'partenerid' => $this->partenerid,
				'pctype' => "1_1|1_2",
				'ipaddr' => $this->ipaddr,
				'paymenttype' => $this->paymenttype,
				'cardtype' => $this->cardtype,
				'reservedField1' => $this->reservedField1,
				'reservedField2' => $this->reservedField2,
                'totype' => $this->title,
                'amount' => $order->order_total,
               // 'order_id' => $order_id,
				'description' => $order_id,
                'orderdescription' => $order_id,
                'redirecturl1' => $this->notify_url,
                'billing_name' => $order->billing_first_name . ' ' . $order->billing_last_name,
                'TMPL_street' => trim($order->billing_address_1, ','),
                'billing_country' => wc()->countries->countries [$order->billing_country],
                'TMPL_state' => $order->billing_state,
                'TMPL_city' => $order->billing_city,
                'TMPL_zip' => $order->billing_postcode,
                'TMPL_telno' => $order->billing_phone,
                'TMPL_emailaddr' => $order->billing_email,
                'delivery_name' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                'delivery_address' => $order->shipping_address_1,
                'delivery_country' => $order->shipping_country,
                'delivery_state' => $order->shipping_state,
                'delivery_tel' => '',
                'delivery_city' => $order->shipping_city,
                'delivery_zip' => $order->shipping_postcode,
                'language' => get_locale(),
                'ipaddr' => $ip,
                'TMPL_COUNTRY' => $woocommerce->customer->get_country(),
                'TMPL_CURRENCY' => $CURRENCY,
				'currency' => $CURRENCY
            );

            //break;
            foreach ($paymentz_args as $param => $value) {
                $paramsJoined[] = "$param=$value";
            }
            $merchant_data = implode('&', $paramsJoined);
            $encrypted_data = encrypt($merchant_data, $this->working_key);
            $paymentz_args_array = array();
            $paymentz_args_array[] = "<input type='hidden' name='encRequest' value='{$encrypted_data}'/>";
            //paymentz elements
            $paymentz_args_array[] = "<input type='hidden' name='toid' value='{$this->merchant_id}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='partenerid' value='{$this->partenerid}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='ipaddr' value='{$this->ipaddr}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='paymenttype' value='{$this->paymenttype}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='cardtype' value='{$this->cardtype}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='reservedField1' value='{$this->reservedField1}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='reservedField2' value='{$this->reservedField2}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='totype' value='{$this->totype}'/>";
			$paymentz_args_array[] = "<input type='hidden' name='pctype' value='1_1|1_2'/>";
            $paymentz_args_array[] = "<input type='hidden' name='amount' value='{$order->order_total}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='orderdescription' value='{$order_id}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='description' value='{$order_id}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='redirecturl' value='{$redirecturl}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_street' value='{$order->billing_address_1}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_state' value='{$order->billing_state}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_city' value='{$order->billing_city}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_zip' value='{$order->billing_postcode}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_telno' value='{$order->billing_phone}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_emailaddr' value='{$order->billing_email}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='language' value='{get_locale()}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='ipaddr' value='{$ip}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_COUNTRY' value='{$country}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='TMPL_CURRENCY' value='{$CURRENCY}'/>";
            $paymentz_args_array[] = "<input type='hidden' name='checksum' value='{$checksum}'/>";
            //$paymentz_args_array[] = "<input type='hidden' name='terminalid' value='2055'/>";

            wc_enqueue_js('
    $.blockUI({
        message: "' . esc_js(__('Thank you for your order. We are now redirecting you to TransactWorld to make payment.', 'woocommerce')) . '",
        baseZ: 99999,
        overlayCSS:
        {
            background: "#fff",
            opacity: 0.6
        },
        css: {
            padding:        "20px",
            zindex:         "9999999",
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:     "24px",
        }
    });
jQuery("#submit_paymentz_payment_form").click();
');

            $form = '<form action="' . esc_url($this->url) . '" method="post" id="paymentz_payment_form" target="_top">
' . implode('', $paymentz_args_array) . '
<!-- Button Fallback -->
<div class="payment_buttons">
<input type="submit" class="button alt" id="submit_paymentz_payment_form" value="' . __('Pay via TransactWorld', 'woocommerce') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
</div>
<script type="text/javascript">
jQuery(".payment_buttons").hide();
</script>
</form>';
            return $form;
        }

        // get all pages
        function get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title)
                $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }

    }

    /**
     * Add the Gateway to WooCommerce
     * */
    function woocommerce_add_pz_paymentz_gateway($methods) {
        $methods[] = 'WC_PZ_Paymentz';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_pz_paymentz_gateway');
}

/*
  Paymentz functions
 */

function encrypt($plainText, $key) {
    $secretKey = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
    $blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
    $plainPad = pkcs5_pad($plainText, $blockSize);
    if (mcrypt_generic_init($openMode, $secretKey, $initVector) != -1) {
        $encryptedText = mcrypt_generic($openMode, $plainPad);
        mcrypt_generic_deinit($openMode);
    }
    return bin2hex($encryptedText);
}

function decrypt($encryptedText, $key) {
    $secretKey = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $encryptedText = hextobin($encryptedText);
    $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
    mcrypt_generic_init($openMode, $secretKey, $initVector);
    $decryptedText = mdecrypt_generic($openMode, $encryptedText);
    $decryptedText = rtrim($decryptedText, "\0");
    mcrypt_generic_deinit($openMode);
    return $decryptedText;
}

//*********** Padding Function *********************

function pkcs5_pad($plainText, $blockSize) {
    $pad = $blockSize - (strlen($plainText) % $blockSize);
    return $plainText . str_repeat(chr($pad), $pad);
}

//********** Hexadecimal to Binary function for php 4.0 version ********

function hextobin($hexString) {
    $length = strlen($hexString);
    $binString = "";
    $count = 0;
    while ($count < $length) {
        $subString = substr($hexString, $count, 2);
        $packedString = pack("H*", $subString);
        if ($count == 0) {
            $binString = $packedString;
        } else {
            $binString .= $packedString;
        }

        $count += 2;
    }
    return $binString;
}

function pz_debug($what) {
    echo '<pre>';
    print_r($what);
    echo '</pre>';
}

/**
  ------------------------------------------------------- Insert Table Starts Here ----------------------------------------------
 * */
function create_paymentz_table() {
    global $wpdb;
    //print_r($wpdb);
    $table_name = $wpdb->prefix . "paymentz_tbl";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(11) NOT NULL AUTO_INCREMENT,
        woocommerce_id varchar(255) NOT NULL,
        order_id varchar(255) NOT NULL,
        order_status varchar(255) NOT NULL,
        tracking_id varchar(255) DEFAULT '' NOT NULL,
        time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY id (id)
        ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_paymentz_table');
/**
  ------------------------------------------------------- Insert Table Ends Here ----------------------------------------------
 * */
/**
  -------------------------------------------------------- Reconcilation Button starts here ---------------------------------------------
 * */
add_action('admin_menu', 'my_plugin_settings');

function my_plugin_settings() {
    add_menu_page('TransactWorld', 'Reconciliation', 'administrator', 'insert-my-meta', 'my_plugin_settings_page', 'dashicons-filter', '54');
}

function my_plugin_settings_page() {
    echo '<div class="container">';
    echo '<br>';
   // echo '<div class="page-header">';
    //echo '<div class="well">';
    //echo '<h3 class="text-center" style="color:#0073aa;">Recon Page</h3>';
  
    //echo '</div>';
   // echo '</div>';
    echo '</div>';
    global $wpdb;
    /*  Paging starts here  */
    $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
    $limit = 15; // number of rows in page
    $offset = ( $pagenum - 1 ) * $limit;
    $total = $wpdb->get_var("SELECT COUNT(`id`) FROM wp_paymentz_tbl where order_status = 'authstarted' " );
    $num_of_pages = ceil($total / $limit);

    /*  Paging ends here  */
    $fivesdrafts = $wpdb->get_results("SELECT * FROM wp_paymentz_tbl WHERE order_status = 'authstarted' LIMIT $offset,$limit");
    if ($fivesdrafts) {
        
            ?>
            <?php
             echo "<p style='color:red;'>";
   if(isset($f_msg))
   {
       echo $f_msg;
   }
   echo "</p>";
   
   echo "<p style='color:green;'>";
   if(isset($s_msg))
   {
       echo $s_msg;
   }
   echo "</p>";
               echo "<br>";
            echo "<div class='container'>";
            echo "<form action='' method='post'>";
            echo "<div class='table-responsive'>";
            echo "<div class='panel panel-default'>";
            echo "<table class='table table-condensed table-hover table-bordered'>";
            echo "<tr  style='background-color:#f1f1f1;'>";
            echo "<th style='color:#0073aa;'> </th>";
            echo "<th style='color:#0073aa;' class='text-center'>Order Number</th>";
            echo "<th style='color:#0073aa;' class='text-center'>Order Description</th>";
            echo "<th style='color:#0073aa;' class='text-center'>Order Title</th>";
            echo "<th style='color:#0073aa;' class='text-center'>Tracking Id</th>";
            echo "<th style='color:#0073aa;' class='text-center'>Order Status</th>";
            echo "</tr>";
            foreach ($fivesdrafts as $post) {
            echo "<tr style='color:#0073aa;'>";
            echo "<td class='text-center'> <input id='checkbox' type='checkbox' name='id[]' value='" . $post->woocommerce_id . "' class='checkbox checkbox-primary'> </td>";
            echo "<td class='text-center'>$post->woocommerce_id</td>";
            echo "<td class='text-center'>$post->order_id</td>";
            echo "<td class='text-center'>$post->time</td>";
             if(!empty($post->tracking_id))
             {
            echo "<td class='text-center'>$post->tracking_id</td>";
             } else{
                 echo "<td class='text-center'>-</td>";
             }
             
            echo "<td class='text-center'>$post->order_status</td>";
            echo "</tr>";
                    }
            echo "</table>";
            echo "</div>"; /* rounded div ends */
            echo "</div>";
            echo "</div>";
            ?>
            <!-- Add the pagination functions here. -->
            <?php
        /* Paging starts here */
        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => __( '&laquo;', 'text-domain' ),
            'next_text' => __( '&raquo;', 'text-domain' ),
            'total' => $num_of_pages,
            'current' => $pagenum
            ));

        if ($page_links) {
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
        }
        /* Paging ends here */
        echo "<div class='container'>";
        echo "<td><input type='submit' value='Recon' name='recon'.$post->woocommerce_id class='btn btn-primary' > </td>";
        echo "</form>";
        echo "</div>";
    } else {
        ?>
        <div class="alert alert-danger">
            <strong>Sorry!</strong> No Records Found.
        </div>
        <?php
    }
    /**
      --------------------------------- Update Query Starts Here ------------------------------------
     * */
    //if (isset($_POST['update'])) 
    if (isset($_POST["recon"]) == "Recon") {
        $pg = new WC_PZ_Paymentz();
        $ids = $_POST["id"];
        foreach ($ids as $id) {
            global $wpdb;
            $wp_paymentz_tbl_var = $wpdb->get_row("SELECT order_id,tracking_id FROM wp_paymentz_tbl where woocommerce_id=".$id);
            if (!empty($wp_paymentz_tbl_var)) {
                $description = $wp_paymentz_tbl_var->order_id;
                $trackingid =null;
                //var_dump($description);
                //var_dump($trackingid);
                $str = $pg->merchant_id . "|" . $description . "|" . $trackingid . "|" . $pg->working_key;
                $checksum = md5($str);
                $request = "toid=" . $pg->merchant_id . "&trackingid=" . $trackingid . "&description=" . $description . "&checksum=" . $checksum;
                $ch = curl_init();
                $url="";
                if ('Y' == $pg->livemode)
                 {
                  $url = "https://".$pg->liveurl."/transaction/SingleCallGenericStatus";
            } 
            else
                {
                $url = "https://".$pg->testurl."/transaction/SingleCallGenericStatus";
            }
                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CAINFO, $ssl);
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array());
                $result = curl_exec($ch);
                //var_dump($result);
                $temp = explode(":", $result);
                $id_pz = implode(",", $_POST["id"]);
                $update_paymentz_tbl = $wpdb->update('wp_paymentz_tbl', array('order_status' => trim($temp[2]),'tracking_id'=>trim($temp[1])), array('woocommerce_id' => $id));
                $woo_commerce_status = "";
                if (trim($temp[2]) == 'capturesuccess' || trim($temp[2]) == 'authsuccessful') {
                    $woo_commerce_status = "wc-processing";
                } else if (trim($temp[2]) == 'authstarted') {
                    $woo_commerce_status = "wc-pending";
                } else {
                    $woo_commerce_status = "wc-failed";
                }
                $wpdb->update('wp_posts', array('post_status' => $woo_commerce_status), array('ID' => $id_pz));
				
				 if ($update_paymentz_tbl) {
                    echo "<p style='color:green;'>Updated Successfully!!!</p>";
                } else {
                    echo "<p style='color:red;'>Error: Record(s) Not Updated!!!</p>";
                }
            }
        }
    }
    /**
      --------------------------------- Update Query Ends Here ------------------------------------
     * */
// echo "<pre>";
// print_r($_POST["id"]);

    //echo "<pre>";
   // print_r($id_pz);

    //echo "<pre>";
   // print_r($update_pz);

// echo "<br>";
// echo "<br>";
// echo "<pre>";
// print_r($wpdb->query);
}

/**
  -------------------------------------------------------- My meta data ends here ---------------------------------------------
 * */
/**
  ---------------------------------- bootstrap starts here --------------------------------------------
 * */
wp_register_script('prefix_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');
wp_enqueue_script('prefix_bootstrap');
// CSS
wp_register_style('prefix_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
wp_enqueue_style('prefix_bootstrap');
/**
  ------------------------------------- bootstrap ends here ------------------------------------------------
 * */
?>