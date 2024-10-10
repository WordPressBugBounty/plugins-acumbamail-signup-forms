<?php
/* PHP Class for Acumbamail API */

class AcumbamailAPI {
    private $auth_token;
    private $customer_id;

    function __construct($customer_id, $auth_token){
        $this->auth_token = $auth_token;
        $this->customer_id = $customer_id;
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

        $subscribers_data = json_encode($subscribers_data);

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

        // getMergeFieldsWordPress($list_id)
    // Obtiene los merge fields de la lista y el tipo que tienen
    public function getMergeFieldsWordPress($list_id){
        $request = "getMergeFieldsWordPress";
        $data = array('list_id' => $list_id);
        return $this->callAPI($request, $data);
    }

	private function delete_cookies_cart(){
		if (isset($_COOKIE['id_acb_ctmer'])) {
			setcookie('id_acb_ctmer', '', time() - (86400 * 2), "/"); 
		}	
		
		if (isset($_COOKIE['id_acb_cart'])) {
			setcookie('id_acb_cart', '', time() - (86400 * 2), "/"); 
		}		
	}   
	
	private function get_email_customer_session() {
		$customer_email = "";
		// Verificar si la sesión está iniciada y contiene datos
		if (WC()->session && WC()->session->get('customer')) {
			$customer_data = WC()->session->get('customer');

			if (isset($customer_data['email'])) {
				$customer_email = $customer_data['email'];
			}
		} 	
		return $customer_email;
	}	 

    	public function paymentCompleteActionWoocommerce($id, $transaction_id )	{
		$request = "paymentCompleteCart";
		$customer_id =  $this->get_or_generate_id_customer();
        	$customer_email = $this->get_email_customer_session();	
        
        	$this->delete_cookies_cart();
		
        	$data = array(
            	'cart_customer_id' => $customer_id,
            	'cart_customer_email' => $customer_email,
			'cart_order_id' => $id,
			// 'transaction_id' => $transaction_id,
        	);	
	
		$data['cart_id'] = $this->get_or_generate_id_cart();
		$this->callAPI($request,$data);			
	}  
    
    	private function get_or_generate_id_customer() {
		if (isset($_COOKIE['id_acb_ctmer'])) {
			$id_customer = $_COOKIE['id_acb_ctmer'];
		} else {
			$id_customer = $this->generate_id_temp_customer();
			setcookie('id_acb_ctmer', $id_customer, time() + (86400 * 2), "/");
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
			$id_customer = $_COOKIE['id_acb_ctmer']; 
		} 
		return $id_customer;		
	}	    
    
    	public function get_or_generate_id_cart() {
		if (isset($_COOKIE['id_acb_cart'])) {
			$id_cart = $_COOKIE['id_acb_cart'];
		} else {
			$id_cart = $this->generate_id_cart();
			setcookie('id_acb_cart', $id_cart, time() + (86400 * 2), "/");
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
		if (WC()->order != null) {
			$order_id =  WC()->order->id;	
		}

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
		$data['cart_product'] = json_encode($product);
		$data['cart_contents_total'] = $cart->get_cart_contents_count();
		$data['cart_total']= $cart->total . " " . get_woocommerce_currency_symbol();
       		$data['registered'] = is_user_logged_in();	
                
		$this->callAPI($request,$data);				
	}
	
	public function newOrderWoocommerce($order_id) {
		$request = "newOrderCart";
		$customer_id = $this->get_or_generate_id_customer(); 
        	$customer_email = $this->get_email_customer_session();		
 
		$data = array(
		    'cart_customer_id' => $customer_id,
		    'cart_customer_email' => $customer_email,
				'cart_order_id' =>$order_id
				//'cart' => $cart->get_cart()
		);	
			
		$data['cart_id'] = $this->get_or_generate_id_cart();
			
		$this->callAPI($request,$data);			
	}  
    
   	public function removeWoocommerceCart($cart, $cart_item_key_remove) {
		$request = "removeItemCart";
			
		$customer_id = $this->get_or_generate_id_customer();
		$customer_email = $this->get_email_customer_session();
		
		$order_id = "";
		if (WC()->order != null) {
			$order_id =  WC()->order->id;	
		}

		$data = array(
		    'cart_customer_id' => $customer_id,
		    'cart_customer_email' => $customer_email,
				'cart_order_id' => $order_id
		);

		$this->check_empty_cart();        
			
		$data['cart_item_key_remove'] = $cart_item_key_remove;
		$cart->calculate_totals();
		$data['cart_total'] =  $cart->total . " " . get_woocommerce_currency_symbol();
		$data['cart_contents_total'] = $cart->get_cart_contents_count();        
		$data['cart_id'] = $this->get_or_generate_id_cart();
		$this->callAPI($request,$data);
		
	}    

    public function submitWoocommerceCart($cart, $event, $cart_item_key_update) {
        $request = "addItemCart";
        
        $customer_id = $this->get_or_generate_id_customer();
        $customer_email = $this->get_email_customer_session();
        
        $order_id = "";
	if (WC()->order != null) {
		$order_id =  WC()->order->id;	
	}

        $data = array(
            'cart_customer_id' => $customer_id,
            'cart_customer_email' => $customer_email,
			'cart_order_id' => $order_id
        );
        
        $product = array();
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
		if ($cart_item_key == $cart_item_key_update) { // We only send the new one or a new unit
			//$data_hash = $cart_item['data_hash'];
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
	$data['cart_product'] = json_encode($product);
	$data['cart_contents_total'] = $cart->get_cart_contents_count();
	$data['cart_total']= $cart->total . " " . get_woocommerce_currency_symbol();
	$data['cart_event'] = $event;
	$data['cart_id'] = $this->get_or_generate_id_cart();
        $data['registered'] = is_user_logged_in();
		
        $this->callAPI($request,$data);
    }
    

    // callAPI($request, $data = array())
    // Realiza la llamada a la API de Acumbamail con los datos proporcionados
    function callAPI($request, $data = array()){
        $url = "https://acumbamail.com/api/1/".$request.'/';

        $fields = array(
            'customer_id' => $this->customer_id,
            'auth_token'=> $this->auth_token,
            'response_type' => 'json',
        );

        if(count($data)!=0){
            $fields=array_merge($fields,$data);
        }

        // $postdata = http_build_query($fields);

        // $opts = array('http' => array(
        //     'method' => 'POST',
        //     'header' => 'Content-type: application/x-www-form-urlencoded',
        //     'content' => $postdata));

        // $response = @file_get_contents($url,
        //                                false,
        //                                stream_context_create($opts));

        // $json = json_decode($response,true);

        $response = @wp_remote_post( $url, array(
            'method' => 'POST',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'header' => 'Content-type: application/x-www-form-urlencoded'
            ),
            'body' => $fields
        ));

        if (is_wp_error($response)) {
            print_r($response);
        }
        elseif (wp_remote_retrieve_response_code($response) != 200) {
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
