<?php
/**
 * Plugin Name: WooCommerce 翔狐科技
 * Plugin URI: https://flyfoxpay.com/
 * Description: 翔狐科技
 * Author: 翔狐科技
 * Author URI: https://flyfoxpay.com/
 * Version: 1.0.2
 */
 
defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + flyfoxpay gateway
 */
function wc_flyfoxpay_add_to_gateways( $gateways ) {
	
	global $woocommerce;
 @$tagevrateverc=$woocommerce->cart->total;
 if($tagevrateverc > 50 AND $tagevrateverc < 5000) {
     $gateways[] = 'WC_Gateway_Flyfoxpay';
 }else{if(!current_user_can('admin')){$gateways[] = 'WC_Gateway_Flyfoxpay';}else{if($tagevrateverc=='' OR $tagevrateverc==null){$gateways[] = 'WC_Gateway_Flyfoxpay';}}}
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_flyfoxpay_add_to_gateways' );


/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_flyfoxpay_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=flyfoxpay_gateway' ) . '">' . __( '設置', 'wc-gateway-flyfoxpay' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_flyfoxpay_gateway_plugin_links' );


/**
 * Flyfoxpay Payment Gateway
 *
 * Provides an Flyfoxpay Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_Flyfoxpay
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		SkyVerge
 */
add_action( 'plugins_loaded', 'wc_flyfoxpay_gateway_init', 11 );

function wc_flyfoxpay_gateway_init() {

	class WC_Gateway_Flyfoxpay extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'flyfoxpay_gateway';
			$this->icon               = apply_filters('woocommerce_flyfoxpay_icon', '');
			$this->has_fields         = true;
			$this->method_title       = __( '翔狐支付', 'wc-gateway-flyfoxpay' );
			$this->method_description = __( '翔狐支付', 'wc-gateway-flyfoxpay' );
			
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->id        = $this->get_option( 'id' );
			$this->key  = $this->get_option( 'key' );
			$this->mail        = $this->get_option( 'mail' );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			 add_action('woocommerce_api_flyfoxpay_callback', array($this, 'ycallback'));
		}
		public function ycallback() {
  	   if(@$_REQUEST['orderid']==null OR @$_REQUEST['orderid']==''){echo '驗證失敗e';}else{
    $url = "https://api.flyfoxpay.com/api/check/";//API位置
 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(
 array("key"=>$this->key, //商家KEY
       "id"=>$this->id, //商家ID
       "mail"=>$this->mail, //商家EMAIL
       "trade_no"=>$_REQUEST['orderid'], //商家訂單ID
       ))); 
$output = curl_exec($ch); 
curl_close($ch);

$security1  = array();

$security1['mchid']      = $this->id;//商家ID

$security1['status']        = "7";//驗證，請勿更改

$security1['mail']      = $this->mail;//商家EMAIL

$security1['trade_no']      = $_REQUEST['orderid'];//商家訂單ID

foreach ($security1 as $k=>$v)

{

    $o.= "$k=".($v)."&";

}

$sign1 = md5(substr($o,0,-1).$this->key);//**********請替換成商家KEY
$json=json_decode($output, true);
if($json['sign']==$sign1){
    $order = wc_get_order( $json['customize1'] );
    	// Mark as on-hold (we're awaiting the payment)
    	$order->payment_complete();
			
     if($_POST['orderid']!=='' OR $_POST['orderid']!==null){
               header('Content-Type: application/json');
               echo '{"ok":"ok"}';}else{
               echo 'success';}
}else{
  echo '驗證失敗';
}}
die();
  }
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_flyfoxpay_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( '啟用/停用', 'wc-gateway-flyfoxpay' ),
					'type'    => 'checkbox',
					'label'   => __( '開啟翔狐支付', 'wc-gateway-flyfoxpay' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( '名稱', 'wc-gateway-flyfoxpay' ),
					'type'        => 'text',
					'description' => __( '自訂義結帳模組名稱', 'wc-gateway-flyfoxpay' ),
					'default'     => __( '翔狐支付', 'wc-gateway-flyfoxpay' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( '說明', 'wc-gateway-flyfoxpay' ),
					'type'        => 'textarea',
					'description' => __( '自訂義客戶看見此模組說明', 'wc-gateway-flyfoxpay' ),
					'default'     => __( '您可以選擇翔狐支付以使用微信或是支付寶結帳', 'wc-gateway-flyfoxpay' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( '結帳完成說明', 'wc-gateway-flyfoxpay' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-flyfoxpay' ),
					'default'     => __( '感謝你使用翔狐支付結帳，你的訂單正在處理中', 'wc-gateway-flyfoxpay' ),
					'desc_tip'    => true,
				),
				'id' => array(
					'title'       => __( '商家ID', 'wc-gateway-flyfoxpays' ),
					'type'        => 'textarea',
					'description' => __( '請輸入翔狐支付商家ID', 'wc-gateway-flyfoxpay' ),
					'default'     => __( '*********', 'wc-gateway-flyfoxpay' ),
					'desc_tip'    => true,
				),
				'key' => array(
					'title'       => __( '商家KEY', 'wc-gateway-flyfoxpays' ),
					'type'        => 'textarea',
					'description' => __( '請輸入翔狐支付商家KEY', 'wc-gateway-flyfoxpay' ),
					'default'     => __( '*********', 'wc-gateway-flyfoxpay' ),
					'desc_tip'    => true,
				),
				'mail' => array(
					'title'       => __( '商家EMAIL', 'wc-gateway-flyfoxpays' ),
					'type'        => 'textarea',
					'description' => __( '請輸入翔狐支付商家EMAIL', 'wc-gateway-flyfoxpay' ),
					'default'     => __( '*********', 'wc-gateway-flyfoxpay' ),
					'desc_tip'    => true,
				),
			) );
		}
	
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	function payment_fields() {
    
	
 
	// I will echo() the form, but you can close PHP tags and print it directly in HTML
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	// Add this action hook if you want your custom payment gateway to support it
	do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
	echo '<div class="form-row form-row-wide"><label>選擇付款方式 <span class="required">*</span></label>
		<select name="type" id="type">
　<option value="all">多種支付方式</option>
</select>
		</div>
		
		<div class="clear"></div>';
 
	do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
}
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	$typesss=$_POST['type'];
	if($typesss=='alipay'){$typessssss='o_alipay';}elseif($typesss=="wxpay"){$typessssss='o_wxpay';}elseif($typesss=="paypal"){$typessssss='o_wxpay';}else{$typessssss='o_alipay';}
			$order = wc_get_order( $order_id );
                    $total_amount = $order->order_total;
                    $get_return_url=$this->get_return_url($order);
                    $order_id = $order->id;
                    $url = "https://api.flyfoxpay.com/api/";//API位置
 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(
 array("key"=>$this->key, //商家KEY
       "id"=>$this->id, //商家ID
       "mail"=>$this->mail, //商家EMAIL
       "trade_no"=>'woo'.time(), //商家訂單ID
       "amount"=>$total_amount, //訂單金額(需大於50)
       "trade_name"=>$order_id, //訂單名稱
       "customize1"=>$order_id,
       "type"=>$typessssss, //指定付款方式，預設為all
       "return"=>$get_return_url//支付完成返回網址
      ))); 
$output = curl_exec($ch); 
curl_close($ch);
/*
回傳格式:
//成功
{"status":"200","url":"https://sc-i.pw/pay/?sign=*****"}
//重複訂單
{"status":"204","error":"重複訂單內容","url":"https://sc-i.pw/pay/?sign=*****"}
//重複訂單ID(trade_no相同)
{"status":"206","error":"重複訂單ID"}
//以下為錯誤項目
{"status":"404","error":"未設置KEY或是ID或MAIL"}
{"status":"400","error":"請檢查ID或是KEY或MAIL是否有誤"}
{"status":"315","error":"請檢查TYPE欄位是否錯誤"}
{"status":"406","error":"金額不可低於50"}
*/ 
$json=json_decode($output, true);
            
		$order->update_status( 'Processing', __( '等待支付,訂單號:'.'woo'.time(), 'wc-gateway-flyfoxpays' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $json['url']
			);
		}
	
  } // end \WC_Gateway_Flyfoxpay class
}
