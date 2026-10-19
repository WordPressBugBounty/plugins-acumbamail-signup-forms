<?php

/*
   Plugin Name: Acumbamail
   Plugin URI: https://acumbamail.com/en/integrations/wordpress/
   Description: Integrate your Acumbamail forms in your Wordpress pages
   Version: 2.0.30
   Author: Acumbamail
   Author URI: https://acumbamail.com
   Text Domain: acumbamail-signup-forms
   Domain Path: /languages
   License: GPLv2
   License URI: http://www.gnu.org/licenses/gpl-2.0.html
   Requires at least: 4.7
   Tested up to: 7.1
   Requires PHP: 7.4
   WC requires at least: 3.6
   WC tested up to: 11.2
   WooCommerce HPOS Compatibility: true
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require('api/acumbamail.class.php');
require('acumbamail_widget.php');

add_action('init', 'acumbamail_load_textdomain' );
add_action('admin_menu', 'acumbamail_configuration');
add_action('admin_init', 'acumbamail_admin_init');
add_action('widgets_init', 'acumbamail_register_widget');

add_action( 'before_woocommerce_init', 'acumbamail_declare_woo_hpos_compatibility');



if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins')))) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
    // checkout blocks
    add_action('woocommerce_init', 'acumbamail_woocommerce_add_subscription_check_field_block');

    add_filter('woocommerce_checkout_fields', 'acumbamail_woocommerce_add_subscription_check_field'); // WC 3.0.0
    add_action('woocommerce_checkout_create_order', 'acumbamail_woocommerce_add_subscription_field_to_order', 10, 2); // WC 3.0.0
    add_action('woocommerce_order_status_changed', 'acumbamail_woocommerce_order_status_changed', 10, 3); // WC 3.0.0
    add_action('init', 'acumbamail_woocommerce_statuses_since');

    //checkout blocks
    add_action('woocommerce_store_api_checkout_update_order_from_request', 'acumbamail_woocommerce_add_subscription_field_to_order_block',10,2); // WC 7.2.0

    if (acumbamail_min_version_cart()) {
        add_action('woocommerce_add_to_cart', 'acumbamail_woocommerce_add_to_cart', 10, 1); // WC 2.5.0
        add_action('woocommerce_cart_item_removed', 'acumbamail_woocommerce_cart_item_removed',10,2); // WC 1.0
        add_action('woocommerce_cart_item_set_quantity', 'acumbamail_woocommerce_cart_item_set_quantity',10,3); // WC 3.6.0
        add_action('woocommerce_new_order', 'acumbamail_woocommerce_new_order_action' ); // WC 2.7.0
        add_action('woocommerce_pre_payment_complete', 'acumbamail_woocommerce_pre_payment_complete_action', 10, 1 );
        add_action('woocommerce_payment_complete', 'acumbamail_woocommerce_payment_complete_action', 10, 2 ); //WC 1.0
        add_action('woocommerce_order_status_completed', 'acumbamail_woocommerce_order_status_completed_action', 10, 1 ); //WC 1.0
        add_action('wp_login', 'acumbamail_custom_action_after_login', 10, 2);
        add_action('template_redirect', 'acumbamail_check_cart_after_login');
        add_action( 'woocommerce_thankyou', 'acumbamail_woocommerce_thankyou_action' ); // WC 1.0

    }
    add_action('wp_ajax_action_update_state_cart', 'acumbamail_update_state_cart');

}

function acumbamail_has_block_in_page( $page, $block_name ) {
    // parse_blocks() exists since WP 5.0; without it there can be no block-based cart/checkout.
    if ( ! function_exists( 'parse_blocks' ) ) {
        return false;
    }

    $page_to_check = get_post( $page );

    if ( null === $page_to_check || 'page' !== $page_to_check->post_type) {
        return false;
    }

    $blocks = parse_blocks( $page_to_check->post_content );
    foreach ( $blocks as $block ) {
        if ( $block_name === $block['blockName'] ) {
            return true;
        }
    }

    return false;
}

function acumbamail_is_checkout_cart_block() {
	try {
        return acumbamail_has_block_in_page( wc_get_page_id('cart'), 'woocommerce/cart' );
	} catch(Exception $e) {
	}
	return false;
}

function acumbamail_is_checkout_block() {
	try {
    	return acumbamail_has_block_in_page( wc_get_page_id('checkout'), 'woocommerce/checkout' );
	} catch(Exception $e){
	}
    return false;
}

function acumbamail_woocommerce_thankyou_action($order_id) {
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
			$api = new AcumbamailAPI('', $options['auth_token']);
			$api->delete_cookies_cart();
		}
	}
}

function acumbamail_get_version_wc() {
    $woo_version = "";
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();

        if ( isset( $all_plugins['woocommerce/woocommerce.php'] ) ) {
            $woo_version = $all_plugins['woocommerce/woocommerce.php']['Version'];
        }
    }
    return $woo_version;
}

function acumbamail_min_version_cart() {
    global $wp_version;
    $wp_ok = version_compare($wp_version, '4.7', '>=' );
    $wc_ok = version_compare(acumbamail_get_version_wc(), '3.6', '>=' );

    return $wp_ok && $wc_ok;
}

/**
 *  Declare the woo HPOS compatibility.
 */
function acumbamail_declare_woo_hpos_compatibility() {

    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}

function acumbamail_cart_enabled_options() {
	$options = get_option('acumbamail_options');
	if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
		return $options;
	}
	return null;
}

// Marks the login per user (not site-wide), so concurrent logins do not consume each other's notice
function acumbamail_custom_action_after_login($user_login, $user) {
	// Site-wide flag used up to 2.0.29 (checked here, on login, to avoid a query on every page)
	if (get_option('custom_user_logged_in') !== false) {
		delete_option('custom_user_logged_in');
	}
	if ($user instanceof WP_User && acumbamail_cart_enabled_options()) {
		update_user_meta($user->ID, '_acumbamail_login_pending', '1');
	}
}

// Sends the login notice on the first page the user loads after logging in
function acumbamail_check_cart_after_login() {
	if (!is_user_logged_in()) {
		return;
	}
	$current_user = wp_get_current_user();
	if (!get_user_meta($current_user->ID, '_acumbamail_login_pending', true)) {
		return;
	}
	delete_user_meta($current_user->ID, '_acumbamail_login_pending');

	$options = acumbamail_cart_enabled_options();
	if (!$options || !function_exists('WC') || is_null(WC())) {
		return;
	}
	// Nothing to link when the cart is empty; later cart events already identify the customer
	$cart = WC()->cart;
	if (!$cart || sizeof($cart->get_cart()) == 0) {
		return;
	}
	$api = new AcumbamailAPI('', $options['auth_token']);
	$api->loguinWoocommerce($current_user, $cart);
}

/**
 * Remembers, for the current request only, the cart items already notified as a quantity change.
 * Adding a product that is already in the cart fires both woocommerce_cart_item_set_quantity and
 * woocommerce_add_to_cart; this avoids a second API call for the same item.
 */
function acumbamail_woocommerce_cart_item_notified( $cart_item_key, $set = false ) {
	static $keys = array();
	if ( $set ) {
		$keys[ $cart_item_key ] = true;
	}
	return isset( $keys[ $cart_item_key ] );
}

function acumbamail_woocommerce_add_to_cart($cart_id) {
	// Already sent as a quantity change in this request (product that was already in the cart)
	if ( acumbamail_woocommerce_cart_item_notified( $cart_id ) ) {
		return;
	}
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');

		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
			$api = new AcumbamailAPI('', $options['auth_token']);

			if (WC()->cart && !is_null(WC()->cart)) {
				WC()->cart->calculate_totals();
				$api->submitWoocommerceCart(WC()->cart, "add", $cart_id);
			} else {
				//error_reporting('no existe WC()->cart o es nulo');
			}
		} else {
			//error_log('esta vacio options o no existe auth_token');
		}
	} else {
		//error_log('No existe WC o es nula.');
	}
}

function acumbamail_woocommerce_cart_item_removed($cart_item_key, $cart) {
	if (function_exists('WC') && !is_null(WC())) {
	    $options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
	   		$api = new AcumbamailAPI('', $options['auth_token']);
			if (WC()->cart && !is_null(WC()->cart)) {
	   			$api->removeWoocommerceCart(WC()->cart, $cart_item_key);
			}
		}
	}
}

function acumbamail_woocommerce_cart_item_set_quantity($cart_item_key, $quantity, $cart) {
	if (function_exists('WC') && !is_null(WC())) {
   		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
   			$api = new AcumbamailAPI('', $options['auth_token']);
			if (WC()->cart && !is_null(WC()->cart)) {
				// WooCommerce fires this hook before recalculating the totals when the quantity changes
				// from the cart page or when adding a product already in the cart
				WC()->cart->calculate_totals();
   				$api->submitWoocommerceCart(WC()->cart, "change_quantity", $cart_item_key);
				acumbamail_woocommerce_cart_item_notified( $cart_item_key, true );
			}
		}
	}
}

function acumbamail_woocommerce_new_order_action( $order_id ){
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
			$api = new AcumbamailAPI('', $options['auth_token']);
			if (WC()->cart && !is_null(WC()->cart)) {
				$api->newOrderWoocommerce($order_id);
			}
		}
	}
}

/**
 * Remembers, for the current request only, the orders whose payment is being completed,
 * so the "completed" status change fired inside payment_complete() does not notify twice.
 */
function acumbamail_woocommerce_payment_in_progress( $order_id, $set = false ) {
	static $orders = array();
	if ( $set ) {
		$orders[ $order_id ] = true;
	}
	return isset( $orders[ $order_id ] );
}

function acumbamail_woocommerce_pre_payment_complete_action( $order_id ) {
	acumbamail_woocommerce_payment_in_progress( $order_id, true );
}

function acumbamail_woocommerce_payment_complete_action( $id, $transaction_id=null ){
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
			$api = new AcumbamailAPI('', $options['auth_token']);
			$api->paymentCompleteActionWoocommerce($id, $transaction_id);
			// Flag to avoid duplicate call from woocommerce_order_status_completed (stored through the order for HPOS)
			$order = wc_get_order($id);
			if ($order) {
				$order->update_meta_data('_acumba_payment_complete_notified', '1');
				$order->save_meta_data();
			}
		}
	}
}

function acumbamail_woocommerce_order_status_completed_action( $order_id ) {
	// Payment being completed in this request: woocommerce_payment_complete notifies right after
	if ( acumbamail_woocommerce_payment_in_progress( $order_id ) ) {
		return;
	}
	// Skip if already notified via woocommerce_payment_complete (payment gateway)
	$order = wc_get_order( $order_id );
	if ( $order && acumbamail_get_order_meta( $order, '_acumba_payment_complete_notified' ) ) {
		return;
	}
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
			$api = new AcumbamailAPI('', $options['auth_token']);
			$api->paymentCompleteActionWoocommerce($order_id);
		}
	}
}

function acumbamail_load_textdomain() {
    //load_plugin_textdomain( 'acumbamail-signup-forms', false, dirname(plugin_basename(__FILE__)) . '/languages' );
}

function acumbamail_register_widget() {
    register_widget('Acumbamail_Widget');
}

function acumbamail_configuration() {
    global $wp_version;
    // Don't delete the following two lines, so that plugin description translations are not removed
    __('Integrate your Acumbamail forms in your Wordpress pages', 'acumbamail-signup-forms');
    __('Show your Acumbamail signup forms easily in your Wordpress pages through a widget.', 'acumbamail-signup-forms');

    $page_acumba = 'acumbamail_options_page';
    add_menu_page(
        __('Manage your subscriptions with Acumbamail', 'acumbamail-signup-forms'),
        'Acumbamail',
        'manage_options',
        'acumbamail',
        $page_acumba,
        plugin_dir_url(dirname(__FILE__) . '/acumbamail.php').'assets/logo.png'
    );

    // WooCommerce section (require min WooCommerce y WP 4.4+)
    if (is_plugin_active( 'woocommerce/woocommerce.php' ) && defined( 'WC_VERSION' ) && version_compare( $wp_version, '4.4', '>=' ) ) {
        add_submenu_page(
            'acumbamail',
            __('Set up the form to be displayed on your Wordpress pages', 'acumbamail-signup-forms'),
            'Woocommerce',
            'manage_options',
            'acumbamail_woocommerce',
            'acumbamail_woocommerce_options_page');
    }

    // Show notice on Acumbamail page when WooCommerce is not active
    if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) || ! defined( 'WC_VERSION' ) ) {
        add_action( 'admin_notices', function () {
            $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $page !== 'acumbamail' ) return;
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Install and activate WooCommerce to unlock the full functionality of this plugin.', 'acumbamail-signup-forms' ) . '</strong></p></div>';
        } );
    }

    // Show notice on Acumbamail page when active theme does not support widget areas
    if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
        add_action( 'admin_notices', function () {
            $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $page !== 'acumbamail' ) return;
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Your active theme does not support widget areas. The Acumbamail widget will not be displayed. Use the WooCommerce integration to subscribe customers automatically.', 'acumbamail-signup-forms' ) . '</strong></p></div>';
        } );
    }

}

function acumbamail_notice_page() {
    $acumbamail_settings_section = 'acumbamail';
    require('inc/notice_page.php');
}

function acumbamail_options_page() {
    $acumbamail_settings_section = 'acumbamail';
    require('inc/admin_page.php');
}

function acumbamail_woocommerce_options_page() {
    $acumbamail_settings_section = 'acumbamail_woocommerce';
    $acumbamail_cart_section = 'acumbamail_woocommerce_cart';
    require('inc/admin_page.php');
}

function acumbamail_register_settings_section($api, $lists, $forms) {
    $options = get_option('acumbamail_options');
    add_settings_section('acumbamail_main',
                         __('Integrate your Acumbamail forms into your Wordpress pages', 'acumbamail-signup-forms'),
                         'acumbamail_options_text',
                         'acumbamail'
    );
    acumbamail_show_auth_token_textbox('acumbamail', 'acumbamail_main');

    if (isset($options['auth_token']) and $lists) {
        $additional_args['field_name'] = 'list_id';
	    $additional_args['lists'] = $lists;

        add_settings_field('acumbamail_list_id',
                            __('List', 'acumbamail-signup-forms') . ': ',
                           'acumbamail_list_id_field',
                           'acumbamail',
                           'acumbamail_main',
                           $additional_args
        );
    }

    if ($forms) {
    	$additional_args['forms'] = $forms;
        add_settings_field('acumbamail_form_id',
                            __('Form', 'acumbamail-signup-forms') . ': ',
                           'acumbamail_form_id_field',
                           'acumbamail',
                           'acumbamail_main',
			   $additional_args
        );
    }
}

function acumbamail_register_woocommerce_settings_section($api, $lists) {
    add_settings_section('acumbamail_woocommerce',
                         __('Configure the Acumbamail list to which your customers will be automatically subscribed', 'acumbamail-signup-forms'),
                         'acumbamail_options_text',
                         'acumbamail_woocommerce');

    acumbamail_show_auth_token_textbox('acumbamail_woocommerce', 'acumbamail_woocommerce');

    if ($lists) {
        $additional_args['field_name'] = 'woocommerce_list_id';
        $additional_args['lists'] = $lists;

        add_settings_field('acumbamail_woocommerce_list_id',
                            __('List', 'acumbamail-signup-forms') . ': ',
                           'acumbamail_list_id_field',
                           'acumbamail_woocommerce',
                           'acumbamail_woocommerce',
                           $additional_args
        );
        add_settings_field('acumbamail_woocommerce_subscription_sentence',
                           __('Checkbox text', 'acumbamail-signup-forms') . ': ',
                           'acumbamail_subscription_sentence_field',
                           'acumbamail_woocommerce',
                           'acumbamail_woocommerce'
        );
        add_settings_field('acumbamail_woocommerce_double_optin',
                           __('Send confirmation email (double opt-in)', 'acumbamail-signup-forms') . ': ',
                           'acumbamail_woocommerce_double_optin_field',
                           'acumbamail_woocommerce',
                           'acumbamail_woocommerce',
                           array('label_for' => 'acumbamail_woocommerce_double_optin')
        );
        add_settings_field('acumbamail_woocommerce_subscribe_statuses',
                           __('Order statuses that subscribe the customer', 'acumbamail-signup-forms') . ': ',
                           'acumbamail_woocommerce_subscribe_statuses_field',
                           'acumbamail_woocommerce',
                           'acumbamail_woocommerce'
        );
    }

}

function acumbamail_register_woocommerce_cart_section($api) {

    $authorized = FALSE;
    $show = FALSE;
    $actv = FALSE;
    $options = get_option('acumbamail_options');
    if (!empty($options) && !empty($options['auth_token'])) {
        $api = new AcumbamailAPI('', $options['auth_token']);
        $authorizedCart = $api->isAuthorizedCart();
        if (!empty($authorizedCart['auth'])) {
            $authorized = $authorizedCart['auth'];
        }
        if (!empty($authorizedCart['show'])) {
            $show = $authorizedCart['show'];
        }
        if (!empty($authorizedCart['actv'])) {
            $actv = $authorizedCart['actv'];
        }

        $options['authorized_cart'] = $authorized;
        update_option('acumbamail_options', $options);
    }

    if ($authorized) {
        add_settings_section('acumbamail_woocommerce_cart',
                            __('Press Update to check the integration with the abandoned cart', 'acumbamail-signup-forms'),
                            'acumbamail_options_text',
                            'acumbamail_woocommerce_cart');

        add_settings_field('acumbamail_woocommerce_enabled_cart',
            __('Settings status', 'acumbamail-signup-forms') . ': ',
            'acumbamail_state_cart_field',
            'acumbamail_woocommerce_cart',
            'acumbamail_woocommerce_cart'
        );

        add_settings_field(
            'acumbamail_woo_cart_update_state_button',
            '',
            'acumbamail_state_cart_button_field',
            'acumbamail_woocommerce_cart',
            'acumbamail_woocommerce_cart'
        );
    } else {
        if ((isset($options) && !empty($options['auth_token']) && !$show && $actv)) {
            add_action( 'admin_notices', function () {
                echo "<div class='notice notice-info'><p><strong>". esc_html__('Abandoned cart integration is only available from Basic plan','acumbamail-signup-forms') .".</strong>";
                echo "<a target='_blank' href='" . esc_url('https://acumbamail.com/account/rate/') ."'> " . esc_html__('View other pricing plans', 'acumbamail-signup-forms') . "</a>";
                echo "</p></div>";
            } );
        }
    }
}

function acumbamail_show_auth_token_textbox($page, $section) {
    add_settings_field('acumbamail_auth_token',
                       __('Auth Token', 'acumbamail-signup-forms') . ': ',
                       'acumbamail_auth_token_field',
                       $page,
                       $section
    );
}

function acumbamail_admin_init() {

    $options = get_option('acumbamail_options');
    register_setting('acumbamail_options', 'acumbamail_options', 'acumbamail_options_validate');

	$page = "";
	if (isset($_GET['page']) && !empty($_GET['page'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = sanitize_text_field(wp_unslash($_GET['page'])); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ($page !== "acumbamail" && $page !== "acumbamail_woocommerce") {
		return false;
	}

    $auth_token = empty($options) ? '' : $options['auth_token'];
    $api = new AcumbamailAPI('', $auth_token);
    $lists = $api->getLists();
    $forms = [];

    if (isset($options['list_id']) and $options['list_id'] != -1) {
        $forms = $api->getForms($options['list_id']);
    }

    acumbamail_register_settings_section($api, $lists, $forms);
    acumbamail_register_woocommerce_settings_section($api, $lists);
    if (acumbamail_min_version_cart()) {
        acumbamail_register_woocommerce_cart_section($api);
    }
}

function acumbamail_compose_options_for_select_html_field($options, $selected_value) {
    foreach ($options as $key => $value) {
        $selected = '';
        if ($selected_value == $key) {
            $selected = 'selected';
        }
        echo "<option value=" . esc_attr($key) . " " . esc_attr($selected) . ">" . esc_attr($value['name']) . "</option>";
    }
}


function acumbamail_get_form_details() {
    $options = get_option('acumbamail_options');
    $api = new AcumbamailAPI('', $options['auth_token']);
    $form_details = $api->getFormDetails($options['form_id']);

    return $form_details;
}

function acumbamail_options_validate($input) {
    // Verificar nonce
    if (isset($_POST['_wpnonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'acumbamail_options-options')) {
            wp_die('Security check failed');
        }
    } else {
        // Si no hay nonce, verificar que sea una llamada legítima de WordPress
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
    }

    if (isset($_POST['reset'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return array();
    }

    if (isset($input['woocommerce_double_optin'])) {
        $input['woocommerce_double_optin'] = $input['woocommerce_double_optin'] === '1' ? '1' : '0';
    }
    if (isset($input['woocommerce_subscribe_statuses'])) {
        $input['woocommerce_subscribe_statuses'] = acumbamail_sanitize_order_statuses($input['woocommerce_subscribe_statuses']);
    }

    $options = get_option('acumbamail_options');
    if (!$options) {
        $options = array();
    }
    foreach ($input as $key => $value) {
        $options[$key] = $value;
    }
    return $options;
}

function acumbamail_auth_token_field() {
    $options = get_option('acumbamail_options');
    $auth_token = empty($options) ? '' : $options['auth_token'];
    echo "<input id='acumbamail_auth_token' name='acumbamail_options[auth_token]' size=20 type='text' value='". esc_attr($auth_token) . "'>";
}

function acumbamail_subscription_sentence_field() {
    $options = get_option('acumbamail_options');
    $value = "";
    if (!empty($options) && !empty($options['subscription_sentence'])) {
        $value = $options['subscription_sentence'];
    }
    echo "<input id='subscription_sentence_field' name='acumbamail_options[subscription_sentence]' size=20 type='text' value='" . esc_attr($value) . "'>";
}

/**
 * Whether checkout subscriptions must be confirmed by email. Disabled by default, as in previous versions.
 */
function acumbamail_woocommerce_double_optin_enabled() {
    $options = get_option('acumbamail_options');
    return isset($options['woocommerce_double_optin']) && $options['woocommerce_double_optin'] === '1';
}

/**
 * Order statuses (without the "wc-" prefix) that trigger the checkout subscription.
 * "processing" always does, as in previous versions; the others are optional.
 */
function acumbamail_woocommerce_subscribe_statuses() {
    $options = get_option('acumbamail_options');
    $extra_statuses = array();
    if (isset($options['woocommerce_subscribe_statuses']) && is_array($options['woocommerce_subscribe_statuses'])) {
        $extra_statuses = acumbamail_sanitize_order_statuses($options['woocommerce_subscribe_statuses']);
    }
    return array_values(array_unique(array_merge(array('processing'), $extra_statuses)));
}

/**
 * Optional order statuses that can also trigger the checkout subscription, with their WooCommerce labels.
 * Fixed list: statuses such as pending, failed, cancelled or refunded make no sense here.
 */
function acumbamail_get_order_statuses() {
    $wc_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array();
    $statuses = array();
    foreach (array('completed', 'on-hold') as $status) {
        $statuses[$status] = isset($wc_statuses['wc-' . $status]) ? $wc_statuses['wc-' . $status] : $status;
    }
    return $statuses;
}

function acumbamail_sanitize_order_statuses($statuses) {
    if (!is_array($statuses)) {
        return array();
    }
    $valid_statuses = array_keys(acumbamail_get_order_statuses());
    $sanitized = array();
    foreach ($statuses as $status) {
        $status = sanitize_key($status);
        if (in_array($status, $valid_statuses, true)) {
            $sanitized[] = $status;
        }
    }
    return array_values(array_unique($sanitized));
}

function acumbamail_woocommerce_double_optin_field() {
    // Hidden field so that unchecking the checkbox is saved (options are merged on save)
    echo "<input type='hidden' name='acumbamail_options[woocommerce_double_optin]' value='0'>";
    echo "<input id='acumbamail_woocommerce_double_optin' name='acumbamail_options[woocommerce_double_optin]' type='checkbox' value='1' " . checked(acumbamail_woocommerce_double_optin_enabled(), true, false) . ">";
    echo "<p class='description' style='max-width:600px'>" . esc_html__('Recommended. Customers become active subscribers only after confirming by email. As the data controller, it is up to you to decide how to collect and record their consent.', 'acumbamail-signup-forms') . "</p>";
}

function acumbamail_woocommerce_subscribe_statuses_field() {
    $selected = acumbamail_woocommerce_subscribe_statuses();
    // Hidden field so that unchecking every status is saved (options are merged on save)
    echo "<input type='hidden' name='acumbamail_options[woocommerce_subscribe_statuses][]' value=''>";
    // "processing" always subscribes: shown checked and disabled so the list reflects every status that subscribes
    $wc_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array();
    $processing_label = isset($wc_statuses['wc-processing']) ? $wc_statuses['wc-processing'] : 'processing';
    echo "<label style='display:block'><input type='checkbox' checked='checked' disabled='disabled'> " . esc_html($processing_label) . " (" . esc_html__('Not optional', 'acumbamail-signup-forms') . ")</label>";
    foreach (acumbamail_get_order_statuses() as $status => $label) {
        echo "<label style='display:block'><input name='acumbamail_options[woocommerce_subscribe_statuses][]' type='checkbox' value='" . esc_attr($status) . "' " . checked(in_array($status, $selected, true), true, false) . "> " . esc_html($label) . "</label>";
    }
    echo "<p class='description' style='max-width:600px'>" . esc_html__('Customers are subscribed when their order changes to one of these statuses. Only once per order, so they receive a single confirmation email.', 'acumbamail-signup-forms') . "</p>";
}

function acumbamail_list_id_field($additional_args) {
    $options = get_option('acumbamail_options');
    $lists = $additional_args['lists'];

    if (!count($lists)) {
        echo "<p>" . esc_html__("Your lists could not be retrieved", 'acumbamail-signup-forms') . ". " . esc_html__("Check that you have created lists and that your hosting allows incoming traffic from Acumbamail", 'acumbamail-signup-forms') .". </p>";
    }
    else {
        echo "<select id='acumbamail_" . esc_attr($additional_args['field_name']) . "' name='acumbamail_options[" . esc_attr($additional_args['field_name']) . "]'>";
        echo "<option value=-1>-- " . esc_html__("Select a list", 'acumbamail-signup-forms') . "--</option>";
        acumbamail_compose_options_for_select_html_field($lists, $options[$additional_args['field_name']] ?? '');
        echo '</select>';
    }
}

function acumbamail_form_id_field($additional_args) {
    $options = get_option('acumbamail_options');
    $api = new AcumbamailAPI('', $options['auth_token']);
    $forms = $additional_args['forms'];

    echo "<select id='acumbamail_form_id' name='acumbamail_options[form_id]'>";
    echo "<option value=-1>-- " . esc_html__("Select a form", 'acumbamail-signup-forms') . "--</option>";
    acumbamail_compose_options_for_select_html_field($forms, $options['form_id']);
    echo "</select>";
}

function acumbamail_options_text() {
}

function acumbamail_woocommerce_add_subscription_check_field_block() {
	if (acumbamail_is_checkout_block() || acumbamail_is_checkout_cart_block()) {
		$options = get_option('acumbamail_options');

		$subscription_sentence = __('Would you like to subscribe to our mailing list?', 'acumbamail-signup-forms');

        if (isset($options) && isset($options['subscription_sentence']) && !empty($options['subscription_sentence'])) {
            $subscription_sentence = $options['subscription_sentence'];
        }

		// Additional checkout fields require WooCommerce 8.9+: on older versions skip the checkbox instead of a fatal error
		if (function_exists('woocommerce_register_additional_checkout_field') && isset($options) && isset($options['woocommerce_list_id']) && !empty($options['woocommerce_list_id'])) {
			woocommerce_register_additional_checkout_field(
				array(
					'id'       => 'acumbamail-signup-forms/acumba_subscribe',
					'label'    => $subscription_sentence,
					'location' => 'contact',
					'type'     => 'checkbox',
					'store_value' => true
				)
			);
		}
	}

}

function acumbamail_woocommerce_add_subscription_check_field($fields) {
    try{
        $options = get_option('acumbamail_options');
        $subscription_sentence = __('Would you like to subscribe to our mailing list?', 'acumbamail-signup-forms');

        if (isset($options) && isset($options['subscription_sentence']) && !empty($options['subscription_sentence'])) {
            $subscription_sentence = $options['subscription_sentence'];
        }

        if (isset($options) && isset($options['woocommerce_list_id']) && !empty($options['woocommerce_list_id'])) {
            $fields['billing']['acumba_subscribe'] = array(
                'type' => 'checkbox',
                'label' => $subscription_sentence,
                'class' => array('form-row-wide'),
                'clear' => true,
                'priority' => 1000
            );
        }
    } catch(Exception $e){}

    return $fields;
}

function acumbamail_woocommerce_add_subscription_field_to_order_block($order, $request) {
	if (acumbamail_is_checkout_block() || acumbamail_is_checkout_cart_block()) {
		$data = $request->get_json_params();
		$additional_fields = $data['additional_fields'] ?? [];

		// Stored through the order (not post meta) so it works with every order storage mode (HPOS);
		// WooCommerce saves the order after this hook
		if ( isset( $additional_fields['acumbamail-signup-forms/acumba_subscribe'] ) && $additional_fields['acumbamail-signup-forms/acumba_subscribe'] ) {
			$order->update_meta_data( 'acumba_subscribe', '1' );
		} else {
			$order->update_meta_data( 'acumba_subscribe', '0' );
		}
	}
}

function acumbamail_woocommerce_add_subscription_field_to_order($order, $data = array()) {
    if (isset($_POST['acumba_subscribe']) && !empty($_POST['acumba_subscribe'])) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing
        // Stored through the order (not post meta) so it works with every order storage mode (HPOS);
        // WooCommerce saves the order after this hook
        $order->update_meta_data('acumba_subscribe', sanitize_text_field(wp_unslash($_POST['acumba_subscribe']))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }
}

/**
 * Reads an order meta value, falling back to post meta for orders saved by versions <= 2.0.28,
 * which wrote it directly with update_post_meta().
 */
function acumbamail_get_order_meta($order, $key) {
    $value = $order->get_meta($key);
    if ($value === '' || $value === null) {
        $value = get_post_meta($order->get_id(), $key, true);
    }
    return $value;
}

/**
 * Stores when configurable order statuses became available (2.0.29), so that orders already
 * subscribed by previous versions (which only used "processing") are not subscribed again.
 */
function acumbamail_woocommerce_statuses_since() {
    $since = get_option('acumbamail_woo_statuses_since');
    if ($since === false) {
        $since = time();
        add_option('acumbamail_woo_statuses_since', $since);
    }
    return (int) $since;
}

function acumbamail_woocommerce_order_status_changed($order_id, $old_status, $new_status) {
    if (!in_array($new_status, acumbamail_woocommerce_subscribe_statuses(), true)) {
        return;
    }
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    // Orders created before updating to 2.0.29 that previous versions already handled
    // (they subscribed the customer when the order reached "processing") are not subscribed again
    $created = $order->get_date_created();
    if ($created && $created->getTimestamp() < acumbamail_woocommerce_statuses_since()) {
        $paid = $order->get_date_paid();
        if ($old_status === 'processing' || ($paid && $paid->getTimestamp() < acumbamail_woocommerce_statuses_since())) {
            return;
        }
    }
    acumbamail_woocommerce_subscribe_client($order_id);
}

function acumbamail_woocommerce_subscribe_client($order_id) {
    // Retrieving email from order object
    //$order = new WC_Order($order_id);
    $order = wc_get_order($order_id);

    if ( ! $order ) {
        return; // pedido no existe
    }
    // Subscribe only once per order, even if it goes through several selected statuses
    if ($order->get_meta('_acumbamail_subscribed')) {
        return;
    }
    $acumba_subscribe = acumbamail_get_order_meta($order, 'acumba_subscribe');
    if ($acumba_subscribe === '' || $acumba_subscribe === null) {
        // Block checkout: WooCommerce also stores the additional field itself, which survives HPOS sync
        // for orders saved by versions <= 2.0.28
        $acumba_subscribe = $order->get_meta('_wc_other/acumbamail-signup-forms/acumba_subscribe');
    }
    if ($acumba_subscribe) {
        $subscriber_fields = array();
        $subscriber_fields['email'] = $order->get_billing_email();
        $options = get_option('acumbamail_options');
        $double_optin = acumbamail_woocommerce_double_optin_enabled() ? '1' : '0';
        $api = new AcumbamailAPI('', $options['auth_token']);
        $response = $api->addSubscriber($options['woocommerce_list_id'], $subscriber_fields, $double_optin);
        $response_code = $api->getLastResponseCode();
        if ($response !== null) {
            $order->update_meta_data('_acumbamail_subscribed', '1');
            $order->save_meta_data();
        } elseif ($response_code >= 400 && $response_code < 500) {
            // Definitive rejection (e.g. subscriber already in the list): do not retry on later statuses
            $order->update_meta_data('_acumbamail_subscribed', 'error_' . $response_code);
            $order->save_meta_data();
        }
    }
}

function acumbamail_state_cart_field() {
    $options = get_option('acumbamail_options');
    $state_cart = '';
	if (isset($options) and !empty($options) and !empty($options['state_cart'])) {
		 $state_cart = $options['state_cart'];
	}
    echo "<input id='acumbamail_state_cart' name='acumbamail_options[state_cart]' size=20 type='text' disabled value='" . esc_attr($state_cart) . "'>";
}

function acumbamail_state_cart_button_field() {
    echo "<button id='acumbamail_woo_cart_update_state_button' class='button button-secondary'>" . esc_html__('Update', 'acumbamail-signup-forms') . "</button>";
}

function acumbamail_update_state_cart() {

    // Verificar nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'acumbamail_cart_nonce')) {
        wp_die('Security check failed');
    }
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $options = get_option('acumbamail_options');
    if (!empty($options) && !empty($options['auth_token'])) {
        $api = new AcumbamailAPI('', $options['auth_token']);
        $activate_cart = $api->isActivateCart();
        // Only update when Acumbamail answered; on an API error keep the current state
        if (is_array($activate_cart) && isset($activate_cart['status'])) {
            $options['state_cart'] = $activate_cart['status'];
            update_option('acumbamail_options', $options);
        }
        echo esc_html(isset($options['state_cart']) ? $options['state_cart'] : '');
    }
    wp_die();
}
