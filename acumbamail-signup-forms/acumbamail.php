<?php

/*
   Plugin Name: Acumbamail
   Plugin URI: https://acumbamail.com/en/integrations/wordpress/
   Description: Integrate your Acumbamail forms in your Wordpress pages
   Version: 2.0.23
   Author: Acumbamail
   Author URI: https://acumbamail.com
   Text Domain: acumbamail-signup-forms
   Domain Path: /languages
   License: GPLv3
   License URI: https://www.gnu.org/licenses/gpl.html
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require('api/acumbamail.class.php');
require('acumbamail_widget.php');

add_action( 'init', 'acumbamail_load_textdomain' );
add_action('admin_menu', 'acumbamail_configuration');
add_action('admin_init', 'acumbamail_admin_init');
add_action('widgets_init', 'register_acumbamail_widget');

if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins')))) {
    add_filter('woocommerce_checkout_fields', 'acumbamail_woocommerce_add_subscription_check_field');
    add_action('woocommerce_checkout_update_order_meta', 'acumbamail_woocommerce_add_subscription_field_to_order');
    add_action('woocommerce_order_status_processing', 'acumbamail_woocommerce_subscribe_client');

    add_action('woocommerce_add_to_cart', 'acumbamail_woocommerce_add_to_cart', 10, 1); // WC 2.5.0
    add_action('woocommerce_cart_item_removed', 'acumbamail_woocommerce_cart_item_removed',10,2); // WC 1.0
	add_action('woocommerce_cart_item_set_quantity', 'acumbamail_woocommerce_cart_item_set_quantity',10,3); // WC 3.6.0
	add_action('woocommerce_new_order', 'acumbamail_woocommerce_new_order_action', 10, 2 ); // WC 2.7.0
	add_action('woocommerce_payment_complete', 'acumbamail_woocommerce_payment_complete_action', 10, 2 ); //WC 1.0
	//add_filter('woocommerce_login_credentials', 'acumbamail_woocommerce_login_credentials', 10); // WC 1.0
    add_action('wp_login', 'acumbamail_custom_action_after_login', 10, 2);
	add_action('template_redirect', 'acumbamail_check_cart_after_login');
}

function acumbamail_custom_action_after_login($user_login, $user) {
	update_option('custom_user_logged_in', true);
	
}

function acumbamail_check_cart_after_login() {
	if (function_exists('WC') && !is_null(WC())) {
		if (get_option('custom_user_logged_in')) {
			$options = get_option('acumbamail_options');
			if (!empty($options) && !empty($options['auth_token'])) {
				// Verificar que haya un usuario autenticado
				$current_user = wp_get_current_user();
				if (!$current_user || 0 === $current_user->ID) {
					error_log('No se encontró ningún usuario autenticado.');
					return;
				}
				// Verificar si el carrito de WooCommerce está disponible y no está vacío
				$cart = WC()->cart;
				if (!$cart || is_null($cart) || sizeof($cart->get_cart()) == 0) {
					error_log('El carrito de WooCommerce no está disponible o está vacío.');
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

		if (!empty($options) && !empty($options['auth_token'])) {
			$api = new AcumbamailAPI('', $options['auth_token']);

			if (WC()->cart && !is_null(WC()->cart)) {
				WC()->cart->calculate_totals();
				$api->submitWoocommerceCart(WC()->cart, "add", $cart_id);
			} else {
				error_reporting('no existe WC()->cart o es nulo');
			}
		} else {
			error_log('esta vacio options o no existe auth_token');
		}
	} else {
		error_log('No existe WC o es nula.');
	}
}

function acumbamail_woocommerce_cart_item_removed($cart_item_key, $cart) { 
	if (function_exists('WC') && !is_null(WC())) {
	    $options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token'])) {
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
		if (!empty($options) && !empty($options['auth_token'])) {
   			$api = new AcumbamailAPI('', $options['auth_token']);
			if (WC()->cart && !is_null(WC()->cart)) {
   				$api->submitWoocommerceCart(WC()->cart, "change_quantity", $cart_item_key);		
			}
		}
	}	
}

function acumbamail_woocommerce_new_order_action( $order_id, $order ){
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token'])) {
			$api = new AcumbamailAPI('', $options['auth_token']);
			if (WC()->cart && !is_null(WC()->cart)) {
				$api->newOrderWoocommerce($order_id);
			}
		}
	}	
}

function acumbamail_woocommerce_payment_complete_action( $id, $transaction_id ){
	if (function_exists('WC') && !is_null(WC())) {
		$options = get_option('acumbamail_options');
		if (!empty($options) && !empty($options['auth_token'])) {
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
       
    } else {
        // Invalid credentials, return an error message
        //return new WP_Error('authentication_failed', __('Las credenciales introducidas no son válidas.', 'woocommerce'));
    }
}

function acumbamail_load_textdomain() {
    load_plugin_textdomain( 'acumbamail-signup-forms', true, dirname(plugin_basename(__FILE__)) . '/languages' );
}

function register_acumbamail_widget() {
    register_widget('Acumbamail_Widget');
}

function acumbamail_configuration() {
    // Don't delete the following two lines, so that plugin description translations are not removed
    __('Integrate your Acumbamail forms in your Wordpress pages', 'acumbamail-signup-forms');
    __('Show your Acumbamail signup forms easily in your Wordpress pages through a widget.', 'acumbamail-signup-forms');
    add_menu_page(
        __('Manage your subscriptions with Acumbamail', 'acumbamail-signup-forms'),
        'Acumbamail',
        'manage_options',
        'acumbamail',
        'acumbamail_options_page',
        plugin_dir_url(dirname(__FILE__) . '/acumbamail.php').'assets/logo.png'
    );

    if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins')))) {
        add_submenu_page('acumbamail',
                         __('Set up the form to be displayed on your Wordpress pages', 'acumbamail-signup-forms'),
                         'Woocommerce',
                         'manage_options',
                         'acumbamail_woocommerce',
                         'acumbamail_woocommerce_options_page');
    }
}

function acumbamail_options_page() {
    $acumbamail_settings_section = 'acumbamail';
    require('inc/admin_page.php');
}

function acumbamail_woocommerce_options_page() {
    $acumbamail_settings_section = 'acumbamail_woocommerce';
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
    $auth_token = empty($options) ? '' : $options['auth_token'];
    $api = new AcumbamailAPI('', $auth_token);
    $lists = $api->getLists();
    $forms = [];

    if (isset($options['list_id']) and $options['list_id'] != -1) {
        $forms = $api->getForms($options['list_id']);
    }
    
    register_setting('acumbamail_options', 'acumbamail_options', 'acumbamail_options_validate');
    
    acumbamail_register_settings_section($api, $lists, $forms);
    acumbamail_register_woocommerce_settings_section($api, $lists);
}

function compose_options_for_select_html_field($options, $selected_value) {
    foreach ($options as $key => $value) {
        $selected = '';
        if ($selected_value == $key) {
            $selected = 'selected';
        }
        echo "<option value=" . $key . " " . $selected . ">" . $value['name'] . "</option>";
    }
}

function acumbamail_get_form_details() {
    $options = get_option('acumbamail_options');
    $api = new AcumbamailAPI('', $options['auth_token']);
    $form_details = $api->getFormDetails($options['form_id']);

    return $form_details;
}

function acumbamail_options_validate($input) {
    if (isset($_POST['reset'])) {
        $output = var_export($_POST, true);
        return array();
    }

    $options = get_option('acumbamail_options');
    foreach ($input as $key => $value) {
        $options[$key] = $value;
    }
    return $options;
}

function acumbamail_auth_token_field() {
    $options = get_option('acumbamail_options');
    $auth_token = empty($options) ? '' : $options['auth_token'];
    echo "<input id='acumbamail_auth_token' name='acumbamail_options[auth_token]' size=20 type='text' value='{$auth_token}'>";
}

function acumbamail_subscription_sentence_field() {
    $options = get_option('acumbamail_options');
    echo "<input id='subscription_sentence_field' name='acumbamail_options[subscription_sentence]' size=20 type='text' value='{$options['subscription_sentence']}'>";
}

function acumbamail_list_id_field($additional_args) {
    $options = get_option('acumbamail_options');
    $lists = $additional_args['lists'];

    if (!count($lists)) {
        echo "<p>" . __("Your lists could not be retrieved", 'acumbamail-signup-forms') . ". " . __("Check that you have created lists and that your hosting allows incoming traffic from Acumbamail", 'acumbamail-signup-forms') .". </p>";
    }
    else {
        echo "<select id='acumbamail_'" . $additional_args['field_name'] . " name='acumbamail_options[" . $additional_args['field_name'] . "]'>";
        echo "<option value=-1>-- " . __("Select a list", 'acumbamail-signup-forms') . "--</option>";
        compose_options_for_select_html_field($lists, $options[$additional_args['field_name']]);
        echo '</select>';
    }
}

function acumbamail_form_id_field($additional_args) {
    $options = get_option('acumbamail_options');
    $api = new AcumbamailAPI('', $options['auth_token']);
    $forms = $additional_args['forms'];

    echo "<select id='acumbamail_form_id' name='acumbamail_options[form_id]'>";
    echo "<option value=-1>-- " . __("Select a form", 'acumbamail-signup-forms') . "--</option>";
    compose_options_for_select_html_field($forms, $options['form_id']);
    echo "</select>";
}

function acumbamail_options_text() {
}

function acumbamail_woocommerce_add_subscription_check_field($fields) {
    $options = get_option('acumbamail_options');
    $subscription_sentence = __('Would you like to subscribe to our mailing list?', 'acumbamail-signup-forms');

    if ($options['subscription_sentence']) {
        $subscription_sentence = $options['subscription_sentence'];
    }

    if ($options['woocommerce_list_id']) {
        $fields['billing']['acumba_subscribe'] = array(
            'type' => 'checkbox',
            'label' => $subscription_sentence,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 1000
        );
    }

    return $fields;
}

function acumbamail_woocommerce_add_subscription_field_to_order($order_id) {
    if ($_POST['acumba_subscribe']) {
        update_post_meta($order_id, 'acumba_subscribe', $_POST['acumba_subscribe']);
    }
}

function acumbamail_woocommerce_subscribe_client($order_id) {
    // Retrieving email from order object
    $order = new WC_Order($order_id);
    $acumba_subscribe = get_post_meta($order_id, 'acumba_subscribe', true);
    if ($acumba_subscribe) {
        $subscriber_fields = array();
        $subscriber_fields['email'] = $order->get_billing_email();
        $options = get_option('acumbamail_options');
        $api = new AcumbamailAPI('', $options['auth_token']);
        $api->addSubscriber($options['woocommerce_list_id'], $subscriber_fields);
    }
}
