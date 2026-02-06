<?php

/*
   Plugin Name: Acumbamail
   Plugin URI: https://acumbamail.com/en/integrations/wordpress/
   Description: Integrate your Acumbamail forms in your Wordpress pages
   Version: 2.0.26
   Author: Acumbamail
   Author URI: https://acumbamail.com
   Text Domain: acumbamail-signup-forms
   Domain Path: /languages
   License: GPLv2
   License URI: http://www.gnu.org/licenses/gpl-2.0.html
   Requires at least: 4.7
   Tested up to: 6.8
   Requires PHP: 7.4
   WC requires at least: 3.6
   WC tested up to: 10.0
   Requires Plugins: woocommerce
   WooCommerce HPOS Compatibility: true
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require('api/acumbamail.class.php');
require('acumbamail_widget.php');

add_action('init', 'acumbamail_load_textdomain' );
add_action('admin_menu', 'acumbamail_configuration');
add_action('admin_init', 'acumbamail_admin_init');
add_action('widgets_init', 'register_acumbamail_widget');

add_action( 'before_woocommerce_init', 'declare_woo_hpos_compatibility');



if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins')))) {
    // checkout blocks
    add_action('woocommerce_init', 'acumbamail_woocommerce_add_subscription_check_field_block');

    add_filter('woocommerce_checkout_fields', 'acumbamail_woocommerce_add_subscription_check_field'); // WC 3.0.0
    add_action('woocommerce_checkout_update_order_meta', 'acumbamail_woocommerce_add_subscription_field_to_order'); // WC 3.0.0
    add_action('woocommerce_order_status_processing', 'acumbamail_woocommerce_subscribe_client'); //WC 1.0.0

    //checkout blocks
    add_action('woocommerce_store_api_checkout_update_order_from_request', 'acumbamail_woocommerce_add_subscription_field_to_order_block',10,2); // WC 7.2.0

    if (min_version_cart()) {
        add_action('woocommerce_add_to_cart', 'acumbamail_woocommerce_add_to_cart', 10, 1); // WC 2.5.0
        add_action('woocommerce_cart_item_removed', 'acumbamail_woocommerce_cart_item_removed',10,2); // WC 1.0
        add_action('woocommerce_cart_item_set_quantity', 'acumbamail_woocommerce_cart_item_set_quantity',10,3); // WC 3.6.0
        add_action('woocommerce_new_order', 'acumbamail_woocommerce_new_order_action' ); // WC 2.7.0
        add_action('woocommerce_payment_complete', 'acumbamail_woocommerce_payment_complete_action', 10, 2 ); //WC 1.0
        //add_filter('woocommerce_login_credentials', 'acumbamail_woocommerce_login_credentials', 10); // WC 1.0
        add_action('wp_login', 'acumbamail_custom_action_after_login', 10, 2);
        add_action('template_redirect', 'acumbamail_check_cart_after_login');
        add_action( 'woocommerce_thankyou', 'acumbamail_woocommerce_thankyou_action' ); // WC 1.0

    }
    add_action('wp_ajax_action_update_state_cart', 'acumbamail_update_state_cart');

}

function has_block_in_page( $page, $block_name ) {
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

function is_checkout_cart_block() {
	try {
        return has_block_in_page( wc_get_page_id('cart'), 'woocommerce/cart' );
	} catch(Exception $e) {
	}
	return false;
}

function is_checkout_block() {
	try {
    	return has_block_in_page( wc_get_page_id('checkout'), 'woocommerce/checkout' );
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

function get_version_wc() {
    $woo_version = "";
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

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

function min_version_cart() {
    global $wp_version;
    $wp_ok = version_compare($wp_version, '4.7', '>=' );
    $wc_ok = version_compare(get_version_wc(), '3.6', '>=' );

    return $wp_ok && $wc_ok;
}

/**
 *  Declare the woo HPOS compatibility.
 */
function declare_woo_hpos_compatibility() {

    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}

function acumbamail_custom_action_after_login($user_login, $user) {
	update_option('custom_user_logged_in', true);
}

function acumbamail_check_cart_after_login() {
	if (function_exists('WC') && !is_null(WC())) {
		if (get_option('custom_user_logged_in')) {
			$options = get_option('acumbamail_options');
			if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
				// Verificar que haya un usuario autenticado
				$current_user = wp_get_current_user();
				if (!$current_user || 0 === $current_user->ID) {
					//No se encontró ningún usuario autenticado
					return;
				}
				// Verificar si el carrito de WooCommerce está disponible y no está vacío
				$cart = WC()->cart;
				if (!$cart || is_null($cart) || sizeof($cart->get_cart()) == 0) {
					//El carrito de WooCommerce no está disponible o está vacío.
					return;
				}
				$api = new AcumbamailAPI('', $options['auth_token']);
				$api->loguinWoocommerce($current_user, $cart);
				delete_option('custom_user_logged_in');
			}
		}
	}
}

function acumbamail_woocommerce_add_to_cart($cart_id) {
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
   				$api->submitWoocommerceCart(WC()->cart, "change_quantity", $cart_item_key);
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

function acumbamail_woocommerce_payment_complete_action( $id, $transaction_id=null ){
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token']) && !empty($options['state_cart']) && $options['state_cart'] == "enabled") {
			$api = new AcumbamailAPI('', $options['auth_token']);
			$api->paymentCompleteActionWoocommerce($id, $transaction_id );
		}
	}
}

function acumbamail_woocommerce_login_credentials($creds) {
	$creds['user_login'] = sanitize_text_field($creds['user_login']);
    $creds['user_password'] = sanitize_text_field($creds['user_password']);

    // Verify if a username or an email address was provided.
    if (isset($creds['user_login'])) {
        $username = $creds['user_login'];
    } else {
        return $creds; // There is not enough data to validate
    }

    // Get the user by username or email address
    $user = get_user_by('login', $username);
    if (!$user) {
        $user = get_user_by('email', $username);
    }

    // Verify if the user exists and the credentials are correct
    if ($user && wp_check_password($creds['user_password'], $user->data->user_pass, $user->ID)) {
        $options = get_option('acumbamail_options');
    	$api = new AcumbamailAPI('', $options['auth_token']);
    	$api->loguinWoocommerce($user);	//
        // Valid credentials, return the username and password
        return array(
            'user_login'    => $user->user_login,
            'user_password' => $creds['user_password'],
            'remember'      => isset($creds['user_remember']) ? $creds['user_remember'] : false,
        );
        //

    }
}

function acumbamail_load_textdomain() {
    //load_plugin_textdomain( 'acumbamail-signup-forms', false, dirname(plugin_basename(__FILE__)) . '/languages' );
}

function register_acumbamail_widget() {
    register_widget('Acumbamail_Widget');
}

function acumbamail_configuration() {
    global $wp_version;
    // Don't delete the following two lines, so that plugin description translations are not removed
    __('Integrate your Acumbamail forms in your Wordpress pages', 'acumbamail-signup-forms');
    __('Show your Acumbamail signup forms easily in your Wordpress pages through a widget.', 'acumbamail-signup-forms');

    $page_acumba = 'acumbamail_options_page';
    // WordPress section (WP <= 5.7.2)
    if ( version_compare( $wp_version, '5.7.2', '>' ) ) {
        $page_acumba = 'acumbamail_notice_page';
    }
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

    // Si no cumple nada
    if ( version_compare( $wp_version, '5.7.2', '>' ) && ( !is_plugin_active( 'woocommerce/woocommerce.php' ) || ! defined( 'WC_VERSION' ) || version_compare( $wp_version, '4.4', '<' ) ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>This plugin require WordPress ≤ 5.7.2 or  4.4 ≤ WordPress < 5.8 with WooCommerce ≥ 3.0.</strong></p></div>';
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
            'acumba_state_cart',
            'acumbamail_woocommerce_cart',
            'acumbamail_woocommerce_cart'
        );

        add_settings_field(
            'acumbamail_woo_cart_update_state_button',
            '',
            'acumba_state_cart_button',
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
    if (min_version_cart()) {
        acumbamail_register_woocommerce_cart_section($api);
    }
}

function compose_options_for_select_html_field($options, $selected_value) {
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
        if (!wp_verify_nonce($_POST['_wpnonce'], 'acumbamail_options-options')) {
            wp_die('Security check failed');
        }
    } else {
        // Si no hay nonce, verificar que sea una llamada legítima de WordPress
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
    }

    if (isset($_POST['reset'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $output = var_export($_POST, true); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export, WordPress.Security.NonceVerification.Missing
        return array();
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

function acumbamail_list_id_field($additional_args) {
    $options = get_option('acumbamail_options');
    $lists = $additional_args['lists'];

    if (!count($lists)) {
        echo "<p>" . esc_html__("Your lists could not be retrieved", 'acumbamail-signup-forms') . ". " . esc_html__("Check that you have created lists and that your hosting allows incoming traffic from Acumbamail", 'acumbamail-signup-forms') .". </p>";
    }
    else {
        echo "<select id='acumbamail_" . esc_attr($additional_args['field_name']) . "' name='acumbamail_options[" . esc_attr($additional_args['field_name']) . "]'>";
        echo "<option value=-1>-- " . esc_html__("Select a list", 'acumbamail-signup-forms') . "--</option>";
        compose_options_for_select_html_field($lists, $options[$additional_args['field_name']] ?? '');
        echo '</select>';
    }
}

function acumbamail_form_id_field($additional_args) {
    $options = get_option('acumbamail_options');
    $api = new AcumbamailAPI('', $options['auth_token']);
    $forms = $additional_args['forms'];

    echo "<select id='acumbamail_form_id' name='acumbamail_options[form_id]'>";
    echo "<option value=-1>-- " . esc_html__("Select a form", 'acumbamail-signup-forms') . "--</option>";
    compose_options_for_select_html_field($forms, $options['form_id']);
    echo "</select>";
}

function acumbamail_options_text() {
}

function acumbamail_woocommerce_add_subscription_check_field_block() {
	if (is_checkout_block() || is_checkout_cart_block()) {
		$options = get_option('acumbamail_options');

		$subscription_sentence = __('Would you like to subscribe to our mailing list?', 'acumbamail-signup-forms');

        if (isset($options) && isset($options['subscription_sentence']) && !empty($options['subscription_sentence'])) {
            $subscription_sentence = $options['subscription_sentence'];
        }

		if (isset($options) && isset($options['woocommerce_list_id']) && !empty($options['woocommerce_list_id'])) {
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
	if (is_checkout_block() || is_checkout_cart_block()) {
		$data = $request->get_json_params();
		$additional_fields = $data['additional_fields'] ?? [];

		if ( isset( $additional_fields['acumbamail-signup-forms/acumba_subscribe'] ) && $additional_fields['acumbamail-signup-forms/acumba_subscribe'] ) {
			update_post_meta( $order->get_id(), 'acumba_subscribe', '1' );
			//error_log( '🟢 Usuario se suscribió' );
		} else {
			update_post_meta( $order->get_id(), 'acumba_subscribe', '0' );
			//error_log( '🔴 Usuario no se suscribió' );
		}
	}
}

function acumbamail_woocommerce_add_subscription_field_to_order($order_id) {
    if (isset($_POST['acumba_subscribe']) && !empty($_POST['acumba_subscribe'])) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing
        update_post_meta($order_id, 'acumba_subscribe', sanitize_text_field(wp_unslash($_POST['acumba_subscribe']))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }
}

function acumbamail_woocommerce_subscribe_client($order_id) {
    // Retrieving email from order object
    //$order = new WC_Order($order_id);
    $order = wc_get_order($order_id);

    if ( ! $order ) {
        return; // pedido no existe
    }
    $acumba_subscribe = get_post_meta($order_id, 'acumba_subscribe', true);
    if ($acumba_subscribe) {
        $subscriber_fields = array();
        $subscriber_fields['email'] = $order->get_billing_email();
        $options = get_option('acumbamail_options');
        $api = new AcumbamailAPI('', $options['auth_token']);
        $api->addSubscriber($options['woocommerce_list_id'], $subscriber_fields);
    }
}

function acumba_state_cart() {
    $options = get_option('acumbamail_options');
    $state_cart = '';
	if (isset($options) and !empty($options) and !empty($options['state_cart'])) {
		 $state_cart = $options['state_cart'];
	}
    echo "<input id='acumbamail_state_cart' name='acumbamail_options[state_cart]' size=20 type='text' disabled value='" . esc_attr($state_cart) . "'>";
}

function acumba_state_cart_button() {
    echo "<button id='acumbamail_woo_cart_update_state_button' class='button button-secondary'>" . esc_html__('Update', 'acumbamail-signup-forms') . "</button>";
}

function acumbamail_update_state_cart() {

    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'acumbamail_cart_nonce')) {
        wp_die('Security check failed');
    }

    $options = get_option('acumbamail_options');
    if (!empty($options) && !empty($options['auth_token'])) {
        $api = new AcumbamailAPI('', $options['auth_token']);
        $activate_cart = $api->isActivateCart();
        $options['state_cart'] = $activate_cart['status'];
        update_option('acumbamail_options', $options);
        echo esc_html($activate_cart['status']);
    }
    wp_die();
}
