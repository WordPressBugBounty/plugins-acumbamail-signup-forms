<?php
/* PHP Class for Acumbamail API */

class AcumbamailAPI {
    private $auth_token;
    // HTTP code of the last API call (0 on connection error)
    private $last_response_code = 0;

    // $customer_id is no longer sent (the API identifies the account by its key); kept for existing callers
    function __construct($customer_id, $auth_token){
        $this->auth_token = $auth_token;
    }

    public function getLastResponseCode() {
        return $this->last_response_code;
    }

    // Only logs when WP_DEBUG is enabled, so a failing API never fills the store's log
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Acumbamail API ' . $message); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    public function setAuthToken($auth_token) {
        $this->auth_token = $auth_token;
    }

    /******* CAMPAIGNS *******/

    /** createCampaign
        Parameters:
            name = Nombre de la campaña (no es público)
            from_name = Nombre del remitente de la campaña
            from_email = El email desde el que se enviará la campaña
            subject = El asunto de la campaña
            content = El HTML que contendrá la campaña
            list = Los identificadores de las listas a las que se enviará la campaña
        Example: createCampaign("Campaign Test", "John Doe", "john@doe.com", "Email Subject", "Email Content",
                  array(
                    '0' => '1000'
                  ));
    **/

    public function createCampaign($name, $from_name, $from_email, $subject, $content, $lists){
        $request = "createCampaign";

        $list_array=array();

        foreach ($lists as $key=>$value) {
            $list_array['lists['.$key.']']=$value;
        }

        $data = array(
            'name'        => $name,
            'from_name'   => $from_name,
            'from_email'  => $from_email,
            'subject'     => $subject,
            'content'     => $content,
        );

        $data=array_merge($data,$list_array);

        return $this->callAPI($request, $data);
    }

    /** getCampaigns
        Example:
            getCampaigns()
    **/

    public function getCampaigns(){
        $request = "getCampaigns";
        return $this->callAPI($request);
    }

    /** getCampaignBasicInformation
        Parameters:
            campaign_id = Identificador de la campaña de la que quieres obtener la información
        Example:
            getCampaignBasicInformation("1000")
    **/

    public function getCampaignBasicInformation($campaign_id){
        $request = "getCampaignBasicInformation";
        $data = array(
            'campaign_id' => $campaign_id
        );
        return $this->callAPI($request, $data);
    }

    /** getCampaignTotalInformation
        Parameters:
            campaign_id = Identificador de la campaña de la que quieres obtener la información
        Example:
            getCampaignTotalInformation("1000")
    **/

    public function getCampaignTotalInformation($campaign_id){
        $request = "getCampaignTotalInformation";
        $data = array(
            'campaign_id' => $campaign_id
        );
        return $this->callAPI($request, $data);
    }

    /** getCampaignHTML
        Parameters:
            campaign_id = Identificador de la campaña de la que quieres obtener la información
        Example:
            getCampaignHTML("1000")
    **/

    public function getCampaignHTML($campaign_id){
        $request = "getCampaignHTML";
        $data = array(
            'campaign_id' => $campaign_id
        );
        return $this->callAPI($request, $data);
    }

    /** getCampaignOpenersByCountries
        Parameters:
            campaign_id = Identificador de la campaña de la que quieres obtener la información
        Example:
            getCampaignOpenersByCountries("1000")
    **/

    public function getCampaignOpenersByCountries($campaign_id){
        $request = "getCampaignOpenersByCountries";
        $data = array(
            'campaign_id' => $campaign_id
        );
        return $this->callAPI($request, $data);
    }

    /** getCampaignInformationByISP
        Parameters:
            campaign_id = Identificador de la campaña de la que quieres obtener la información
        Example:
            getCampaignInformationByISP("1000")
    **/

    public function getCampaignInformationByISP($campaign_id){
        $request = "getCampaignInformationByISP";
        $data = array(
            'campaign_id' => $campaign_id
        );
        return $this->callAPI($request, $data);
    }

    /******* SUSCRIBERS *******/

    /** createList
        Parameters:
            sender_email = El email que se utilizará para las notificaciones de la lista
            name = Nombre de la lista
            company = La empresa a la que pertenece la lista
            country = El país de procedencia de la lista
            city = La ciudad de la empresa
            address = La dirección de la empresa
            phone = El teléfono de la empresa
        Example:
            createList("john@doe.com", "John Doe", "MailServicios", "Spain", "Madrid", "Calle Falsa, 1", "91000000")
    **/

    public function createList($sender_email,$name,$company,$country,$city,$address,$phone){
        $request = "createList";
        $data = array(
            'sender_email' => $sender_email,
            'name'         => $name,
            'company'      => $company,
            'country'      => $country,
            'city'         => $city,
            'address'      => $address,
            'phone'        => $phone,
        );
        return $this->callAPI($request, $data);
    }

    /** deleteList
        Parameters:
            list_id = Identificador de la lista
        Example:
            deleteList("1000")
    **/

    public function deleteList($list_id){
        $request = "deleteList";
        $data = array('list_id' => $list_id);
        return $this->callAPI($request, $data);
    }

    /** deleteSubscriber
        Parameters:
            list_id = Identificador de la lista
            email = Email del suscriptor
        Example:
            deleteSubscriber("1000", "john2@doe.com")
    **/

    public function deleteSubscriber($list_id, $email){
        $request = "deleteSubscriber";
        $data = array(
            'list_id' => $list_id,
            'email' => $email,
        );
        return $this->callAPI($request, $data);
    }

    /** getSubscribers
        Parameters:
            list_id = Identificador de la lista
            status = (Opcional) Segmenta los suscriptores por estado.
                                0: suscriptores activos.
                                1: suscriptores sin verificar.
                                2: suscriptores que se han dado de baja.
                                3: suscriptores hard bounced.
                                4: suscriptores que se han quejado.
        Example:
            getSubscribers("1000", "0")
    **/

    public function getSubscribers($list_id, $status = ""){
        $request = "getSubscribers";
        $data = array(
            'list_id' => $list_id,
            'status' => $status,
        );
        return $this->callAPI($request, $data);
    }

    /** batchDeleteSubscribers
        Parameters:
            list_id = Identificador de la lista
            email_list = Diccionario de suscriptores
        Example: batchDeleteSubscribers("1000", array(
                                                    '0' => 'john2@doe.com'
                                                ));
    **/
    public function batchDeleteSubscribers($list_id,$email_list){
        $request = "batchDeleteSubscribers";

        $list_array=array();

        foreach ($email_list as $key=>$value) {
            $list_array['email_list['.$key.']']=$value;
        }

        $data = array(
            'list_id' => $list_id,
        );

        $data=array_merge($data,$list_array);

        return $this->callAPI($request, $data);
    }

    /** addMergeTag
        Parameters:
            list_id = Identificador de la lista
            field_name = El nombre la columna que se va a agregar a la lista
            field_type = El tipo de la columna que se va a agregar a la lista
        Example: addMergeTag("1000", "nombre", "char");
    **/

    public function addMergeTag($list_id,$field_name,$field_type){
        $request = "addMergeTag";
        $data = array(
            'list_id' => $list_id,
            'field_name' => $field_name,
            'field_type' => $field_type,
        );
        return $this->callAPI($request, $data);
    }

    /** addSubscriber
        Parameters:
            list_id = Identificador de la lista
            merge_fields = Diccionario que contenga los merge tags del suscriptor como claves y el valor que se quiere agregar al suscriptor
        Example: addSubscriber("1000", array(
                                            'email'  => 'pruebasuscriptor@gmail.com',
                                            'nombre' => 'John'
                                        ));
    **/

    public function addSubscriber($list_id,$merge_fields,$double_optin='',$welcome_email=''){
        $request = "addSubscriber";
        $merge_fields_send=array();

        foreach (array_keys($merge_fields) as $merge_field) {
            $merge_fields_send['merge_fields['.$merge_field.']']=$merge_fields[$merge_field];
        }

        $data = array(
            'list_id' => $list_id,
            'double_optin' => $double_optin,
	    'welcome_email' => $welcome_email,
        );

        $data=array_merge($data,$merge_fields_send);

        return $this->callAPI($request, $data);
    }

    /** getSubscriberDetails
        Parameters:
            list_id = Identificador de la lista
            subscriber = Email del suscriptor
        Example: addSubscriber("1000", "john2@doe.com");
    **/

    public function getSubscriberDetails($list_id,$subscriber){
        $request = "getSubscriberDetails";
        $data = array(
            'list_id' => $list_id,
            'subscriber' => $subscriber,
        );
        return $this->callAPI($request, $data);
    }

    /** getListStats
        Parameters:
            list_id = Identificador de la lista
        Example: getListStats("1000");
    **/

    public function getListStats($list_id){
        $request = "getListStats";
        $data = array('list_id' => $list_id);
        return $this->callAPI($request, $data);
    }

    /** getLists
            Example: getLists();
    **/

    public function getLists(){
        $request = "getListsInternal";
        return $this->callAPI($request);
    }

    /** getForms
        Parameters:
            list_id = Identificador de la lista
        Example: getForms("1000");
    **/
    public function getForms($list_id) {
        $request = "getFormsInternal";
        $data = array('list_id' => $list_id);
        return $this->callAPI($request, $data);
    }

    /** getForms
        Parameters:
            form_id = Identificador del formulario
        Example: getFormDetails("1000");
    **/
    public function getFormDetails($form_id) {
        $request = "getFormDetails";
        $data = array('form_id' => $form_id);
        return $this->callAPI($request, $data);
    }

    /** getFields
            Parameters:
                list_id = Identificador de la lista
            Example: getFields("1000");
    **/

    public function getFields($list_id){
        $request = "getFields";
        $data = array('list_id' => $list_id);
        return $this->callAPI($request, $data);
    }

    /** batchAddSubscribers
            Parameters:
                list_id = Identificador de la lista
                subscribers_data = Un array que contenga los mergetags del suscriptor como
                                   claves y el valor que se quiere agregar al suscriptor.
            Example: batchAddSubscribers("1000", array(
                                                        array("email" => "john@doe.com"),
                                                        array("email" => "john2@doe.com"),));
    **/
    public function batchAddSubscribers($list_id, $subscribers_data){
        $request = "batchAddSubscribers";

        $subscribers_data = wp_json_encode($subscribers_data);

        $data = array(
            "list_id" => $list_id,
            "subscribers_data" => $subscribers_data
        );

        $this->callAPI($request, $data);
    }

    /** getCampaignOpeners
            Parameters:
                campaign_id = Identificador de la campaña de la que quieres obtener la información
            Example: getCampaignOpeners(1000);
    **/

    public function getCampaignOpeners($campaign_id){
        $request = "getCampaignOpeners";

        $data = array(
            "campaign_id" => $campaign_id,
        );

        return $this->callAPI($request, $data);
    }

    // Obtiene los merge fields de la lista y el tipo que tienen
    public function getMergeFieldsWordPress($list_id){
        $request = "getMergeFieldsWordPress";
        $data = array('list_id' => $list_id);
        return $this->callAPI($request, $data);
    }

	// Abandoned cart cookies: same name, path and host as before, plus secure (on https), httponly and SameSite
	private function set_cart_cookie($name, $value, $expires) {
		setcookie($name, $value, array(
			'expires'  => $expires,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		));
	}

	function delete_cookies_cart(){
		if (isset($_COOKIE['id_acb_ctmer'])) {
			$this->set_cart_cookie('id_acb_ctmer', '', time() - (86400 * 2));
		}

		if (isset($_COOKIE['id_acb_cart'])) {
			$this->set_cart_cookie('id_acb_cart', '', time() - (86400 * 2));
		}
	}

	/**
	 * Email sent with cart events: only the account email of a logged-in customer.
	 * Anonymous shoppers are not identified (not even with the email typed at checkout).
	 */
	private function get_cart_customer_email() {
		if (is_user_logged_in()) {
			$user = wp_get_current_user();
			if ($user && $user->exists()) {
				return $user->user_email;
			}
		}
		return "";
	}

	/**
	 * Currency of the cart, sent apart from cart_total (which keeps the symbol appended, as an HTML entity,
	 * for servers that only read cart_total). Must go in every call that sends cart_total: the server
	 * clears the stored symbol when a cart update does not include it.
	 */
	private function add_cart_currency($data, $cart) {
		$data['cart_currency_symbol'] = html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
		$data['cart_currency'] = get_woocommerce_currency();
		$data['cart_amount'] = wc_format_decimal($cart->get_total('edit'));
		return $data;
	}

	/**
	 * Email sent with order events: the account email of the order's customer, never the
	 * current user (an admin can complete the order, or the gateway can notify with no session).
	 * Guest orders are anonymous.
	 */
	private function get_order_customer_email($order) {
		if ($order && $order->get_customer_id()) {
			$user = get_userdata($order->get_customer_id());
			if ($user) {
				return $user->user_email;
			}
		}
		return "";
	}

    public function paymentCompleteActionWoocommerce($id, $transaction_id=null )	{
		$request = "paymentCompleteCart";

        $order = wc_get_order($id);
        $id_acb_cart = $order->get_meta('id_acb_cart');
        $id_acb_ctmer = $order->get_meta('id_acb_ctmer');

        $customer_id = $id_acb_ctmer;
        $customer_email = $this->get_order_customer_email($order);

        $this->delete_cookies_cart();

        $data = array(
            'cart_customer_id' => $customer_id,
            'cart_customer_email' => $customer_email,
            'cart_order_id' => $id,
        );

        $data['cart_id'] = $id_acb_cart;
		$this->callAPI($request,$data);
	}

    private function save_id_customer($order_id) {
		if (is_user_logged_in()) {
            $id_acb_ctmer = get_current_user_id();
        } else {
            $id_acb_ctmer = isset($_COOKIE['id_acb_ctmer']) ? sanitize_text_field(wp_unslash($_COOKIE['id_acb_ctmer'])) : '';
        }
        $order = wc_get_order($order_id);
        $order->update_meta_data('id_acb_ctmer',$id_acb_ctmer);
        $order->save();
    }

    private function save_id_cart($order_id) {
        $id_acb_cart = isset($_COOKIE['id_acb_cart']) ? sanitize_text_field(wp_unslash($_COOKIE['id_acb_cart'])) : '';
        $order = wc_get_order($order_id);
        $order->update_meta_data('id_acb_cart', $id_acb_cart);
        $order->save();
    }

    /**
     * Returns the id of the order the customer has created but not paid yet,
     * so that cart updates do not wipe it in Acumbamail. Empty if there is none.
     */
    private function get_pending_order_id() {
        if (!function_exists('WC') || !WC()->session) {
            return "";
        }

        // Classic checkout stores it in order_awaiting_payment, block checkout in store_api_draft_order
        foreach (array('order_awaiting_payment', 'store_api_draft_order') as $key) {
            $order_id = absint(WC()->session->get($key));
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order && $order->has_status(array('pending', 'failed'))) {
                    return (string) $order_id;
                }
            }
        }

        return "";
    }

    private function get_or_generate_id_customer() {
		if (isset($_COOKIE['id_acb_ctmer'])) {
			$id_customer = sanitize_text_field(wp_unslash($_COOKIE['id_acb_ctmer']));
		} else {
			$id_customer = $this->generate_id_temp_customer();
			$this->set_cart_cookie('id_acb_ctmer', $id_customer, time() + (86400 * 2));
		}

		if (is_user_logged_in()) {
				return  get_current_user_id();
		}

		return $id_customer;
	}

	public function generate_id_cart() {
    		return wp_generate_uuid4();
	}

	public function generate_id_temp_customer() {
		return "t_" . bin2hex(random_bytes(30 / 2));
	}

	public function get_id_temp_customer() {
		$id_customer = "";
		if (isset($_COOKIE['id_acb_ctmer'])) {
			$id_customer = sanitize_text_field(wp_unslash($_COOKIE['id_acb_ctmer']));
		}
		return $id_customer;
	}

    public function get_or_generate_id_cart() {
		if (isset($_COOKIE['id_acb_cart'])) {
			$id_cart = sanitize_text_field(wp_unslash($_COOKIE['id_acb_cart']));
		} else {
			$id_cart = $this->generate_id_cart();
			$this->set_cart_cookie('id_acb_cart', $id_cart, time() + (86400 * 2));
		}

		return $id_cart;
	}

	public function check_empty_cart() {
		$cart_contents = WC()->cart->get_cart_contents_count();

		if ($cart_contents == 0) {
			$this->delete_cookies_cart();
		}
	}

   	public function get_email_customer($user_id) {
		// Retrieve the user's data
		$user_data = get_userdata($user_id);

		// Verify if the user's data was retrieved correctly
		if ($user_data) {
			// Return the user's email address
			return $user_data->user_email;
		} else {
			// Return null if the user was not found
			return null;
		}
	}

	public function loguinWoocommerce($user, $cart) {
		$request = "loginUserCart";
		$customer_id = $this->get_id_temp_customer();
        $order_id = "";

		$data = array(
			'cart_customer_id' => $customer_id,
		    'cart_user_id' => $user->ID,
		    'cart_customer_email' => $this->get_email_customer($user->ID),
		    'cart_order_id' => $order_id
		);

		$data['cart_id'] = $this->get_or_generate_id_cart();

		$product = array();
		foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
			$product_data = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$quantity = $cart_item['quantity'];
			$price =  WC()->cart->get_product_price( $product_data );
			$link = $product_data->get_permalink( $cart_item );

			array_push(
				$product,
				array(
					'cart_item_id' => $cart_item_key,
					'cart_product_id' => $product_id,
					'cart_quantity' => $quantity,
					'cart_price' => $price,
					'cart_link' => $link
				)
			);
		}

		$data['cart_url'] =  wc_get_cart_url();
		$data['cart_product'] = wp_json_encode($product);
		$data['cart_contents_total'] = $cart->get_cart_contents_count();
		$data['cart_total']= $cart->get_total('edit') . " " . get_woocommerce_currency_symbol();
		$data = $this->add_cart_currency($data, $cart);
       	$data['registered'] = is_user_logged_in();

		$this->callAPI($request,$data);
	}

	public function newOrderWoocommerce($order_id) {
		$request = "newOrderCart";
		$customer_id = $this->get_or_generate_id_customer();
        $customer_email = $this->get_order_customer_email(wc_get_order($order_id));

		$data = array(
		    'cart_customer_id' => $customer_id,
		    'cart_customer_email' => $customer_email,
			'cart_order_id' =>$order_id
		);

		$data['cart_id'] = $this->get_or_generate_id_cart();

        $this->save_id_customer($order_id);
        $this->save_id_cart($order_id);

		$this->callAPI($request,$data);
	}

   	public function removeWoocommerceCart($cart, $cart_item_key_remove) {
        $request = "removeItemCart";
		$customer_id = $this->get_or_generate_id_customer();
		$customer_email = $this->get_cart_customer_email();

		$order_id = "";

		$data = array(
		    'cart_customer_id' => $customer_id,
		    'cart_customer_email' => $customer_email,
			'cart_order_id' => $order_id
		);

		$this->check_empty_cart();

		$data['cart_item_key_remove'] = $cart_item_key_remove;
		$cart->calculate_totals();
		$data['cart_total'] =  $cart->get_total('edit') . " " . get_woocommerce_currency_symbol();
		$data = $this->add_cart_currency($data, $cart);
		$data['cart_contents_total'] = $cart->get_cart_contents_count();
		$data['cart_id'] = $this->get_or_generate_id_cart();
		$this->callAPI($request,$data);

	}

    public function submitWoocommerceCart($cart, $event, $cart_item_key_update) {
        $request = "addItemCart";

        $customer_id = $this->get_or_generate_id_customer();
        $customer_email = $this->get_cart_customer_email();

        $order_id = $this->get_pending_order_id();

        $data = array(
            'cart_customer_id' => $customer_id,
            'cart_customer_email' => $customer_email,
			'cart_order_id' => $order_id
        );

        $product = array();
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item_key == $cart_item_key_update) { // We only send the new one or a new unit
                $product_data = $cart_item['data'];
                $product_id = $cart_item['product_id'];
                $quantity = $cart_item['quantity'];
                $price =  WC()->cart->get_product_price( $product_data );
                $link = $product_data->get_permalink( $cart_item );

                array_push(
                    $product,
                    array(
                        'cart_item_id' => $cart_item_key,
                        'cart_product_id' => $product_id,
                        'cart_quantity' => $quantity,
                        'cart_price' => $price,
                        'cart_link' => $link
                    )
                );
            }
	    }

        $data['cart_url'] =  wc_get_cart_url();
        $data['cart_product'] = wp_json_encode($product);
        $data['cart_contents_total'] = $cart->get_cart_contents_count();
        $data['cart_total']= $cart->get_total('edit') . " " . get_woocommerce_currency_symbol();
        $data = $this->add_cart_currency($data, $cart);
        $data['cart_event'] = $event;
        $data['cart_id'] = $this->get_or_generate_id_cart();
        $data['registered'] = is_user_logged_in();

        $this->callAPI($request,$data);
    }

    public function isActivateCart() {
        $request = "activeCustomerCart";
        return $this->callAPI($request);
    }

    public function isAuthorizedCart() {
        $request = "authorizedCart";
        return $this->callAPI($request);
    }


    // Realiza la llamada a la API de Acumbamail con los datos proporcionados
    function callAPI($request, $data = array()){
        $url = "https://acumbamail.com/api/1/".$request.'/';
        $this->last_response_code = 0;

        if (empty($this->auth_token)) {
            return null;
        }

        $fields = array(
            'response_type' => 'json',
        );

        if(count($data)!=0){
            $fields=array_merge($fields,$data);
        }

        $response = @wp_remote_post( $url, array(
            'method' => 'POST',
            'httpversion' => '1.0',
            'blocking' => true,
            'sslverify' => true,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer ' . $this->auth_token,
                'X-Acumbamail-Plugin' => 'wordpress/' . ACUMBAMAIL_PLUGIN_VERSION,
            ),
            'body' => $fields
        ));

        if (is_wp_error($response)) {
            $this->log_error($request . ' error: ' . $response->get_error_message());
            return null;
        }
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $this->last_response_code = $response_code;
        // Some methods (e.g. addSubscriber) answer 201 on success
        if ($response_code < 200 || $response_code >= 300) {
            $this->log_error($request . ' error: HTTP ' . $response_code);
            // Never print here: this also runs during checkout and order status changes, where output breaks
            // JSON responses and redirects. Show the error as an admin notice instead.
            if (is_admin() && !wp_doing_ajax()) {
                add_action('admin_notices', function () use ($response_code) {
                    /* translators: %d: HTTP response code */
                    echo '<div class="notice notice-error"><p><strong>' . esc_html(sprintf(__('Could not connect to Acumbamail. Error %d', 'acumbamail-signup-forms'), $response_code)) . '</strong></p></div>';
                });
            }
            return null;
        }
        else {
            $json = json_decode($response['body'], true);
            if(is_array($json)){
                return $json;
            }else{
                return $response;
            }
        }
    }
}
?>
