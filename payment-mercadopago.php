<?php
/**
 * CampTix MercadoPago Payment Class.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

if ( class_exists( 'CampTix_Payment_Method' ) && ! class_exists( 'CampTix_Payment_Method_MercadoPago' ) ) :
/**
 * Implements the MercadoPago payment gateway.
 */
class CampTix_Payment_Method_MercadoPago extends CampTix_Payment_Method {

	/**
	 * Payment variables.
	 */
	public $id = 'mercadopago';
	public $name = 'MercadoPago';
	public $description = 'MercadoPago';
	public $supported_currencies = array( 'ARS', 'BRL', 'MXN', 'VEF', 'COP' );

	// MercadoPago API URLs.
	protected $payment_url     = 'https://api.mercadolibre.com/checkout/preferences?access_token=';
	protected $ipn_url         = 'https://api.mercadolibre.com/collections/notifications/';
	protected $sandbox_ipn_url = 'https://api.mercadolibre.com/sandbox/collections/notifications/';
	protected $oauth_token     = 'https://api.mercadolibre.com/oauth/token';


	/**
	 * We can have an array to store our options.
	 * Use $this->get_payment_options() to retrieve them.
	 */
	protected $options;

	/**
	 * Init the gataway.
	 *
	 * @return void
	 */
	public function camptix_init() {

		$this->options = array_merge(
			array(
				'client_id'     => '',
				'client_secret' => '',
				'sandbox'       => true,
			),
			$this->get_payment_options()
		);

		// Fix the description for translations.
		$this->description = __( 'MercadoPago Gateway works by sending the user to MercadoPago.com to enter their payment information and complete the payment.', 'camptix-mp' );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );

		// Initialize Latin American Currencies supported by MercadoPago
		add_filter( 'camptix_currencies', array( &$this, 'add_mercadopago_currencies' ) );
	}

	/**
	 * Sets payment settings fields.
	 *
	 * @return void
	 */
	public function payment_settings_fields() {

		$this->add_settings_field_helper( 'client_id',     __( 'Client ID',     'camptix-mp' ), array( $this, 'field_text' ) );
		$this->add_settings_field_helper( 'client_secret', __( 'Client Secret', 'camptix-mp' ), array( $this, 'field_text' ) );
		$this->add_settings_field_helper( 'sandbox',       __( 'Sandbox Mode',  'camptix-mp' ), array( $this, 'field_yesno' ),
				 __( 'MercadoPago sandbox can be used to test payments.', 'camptix-mp' )
			);

	}

	/**
	 * Validate options.
	 *
	 * @param array $input Options.
	 *
	 * @return array       Valide options.
	 */
	public function validate_options( $input ) {

		$output = $this->options;

		if ( ! empty( $input['client_id'] ) ) {
			$output['client_id'] = $input['client_id'];
		}

		if ( ! empty( $input['client_secret'] ) ) {
			$output['client_secret'] = $input['client_secret'];
		}

		if ( isset( $input['sandbox'] ) ) {
			$output['sandbox'] = (bool) $input['sandbox'];
		}

		return $output;
	}

	/**
	 * Add Latin American currencies
	 *
	 * @param array $currencies List of Camptix currencies.
	 *
	 * @return array       All currencies
	 */
	public function add_mercadopago_currencies( $currencies ) {

		$currencies['ARS'] = array ( 'label'  => __( 'Argentine peso',     'camptix-mp' ), 'format' => 'ARS %s' );
		$currencies['BRL'] = array ( 'label'  => __( 'Brazilian real',     'camptix-mp' ), 'format' => 'R$ %s' );
		$currencies['COP'] = array ( 'label'  => __( 'Colombian peso',     'camptix-mp' ), 'format' => 'COP %s' );
		$currencies['MXN'] = array ( 'label'  => __( 'Mexican peso',       'camptix-mp' ), 'format' => 'MXN %s' );
		$currencies['VEF'] = array ( 'label'  => __( 'Venezuelan bolívar', 'camptix-mp' ), 'format' => 'Bs %s' );

		asort( $currencies );

		return $currencies;

	}


	/**
	 * Get client token.
	 *
	 * @return mixed Success returns the token and error returns null.
	 */
	protected function get_client_credentials() {

		$this->log( __( 'Getting MercadoPago client credentials...', 'camptix-mp' ), null, $this->options['client_id'] );

		// Set postdata.
		$postdata  = 'grant_type=client_credentials';
		$postdata .= '&client_id=' . $this->options['client_id'];
		$postdata .= '&client_secret=' . $this->options['client_secret'];

		// Built wp_remote_post params.
		$params = array(
			'body'          => $postdata,
			'sslverify'     => true,
			'timeout'       => 60,
			'headers'       => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded'
			)
		);

		$response = wp_remote_post( $this->oauth_token, $params );

		// Check to see if the request was valid and return the token.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && ( strcmp( $response['response']['message'], 'OK' ) == 0 ) ) {

			$token = json_decode( $response['body'] );

			$this->log( __( 'Received valid response from MercadoPago', 'camptix-mp' ), null, $this->options['client_id'] );

			return $token->access_token;
		} else {
			$this->log( __( 'Received invalid response from MercadoPago.', 'camptix-mp' ), null, $response );
		}

		return null;
	}

	/**
	 * Generate the payment arguments.
	 *
	 * @param  object $order Order data.
	 *
	 * @return array         Payment arguments.
	 */
	public function get_payment_args( $payment_token ) {

		// Get the order information
		$order = $this->get_order( $payment_token );

		$this->log( __( 'Payment arguments for order', 'camptix-mp' ), null, $order );

		// Sets the MercadoPago item description.
		$item_description = __( 'Event', 'camptix-mp' );

		if ( ! empty( $this->camptix_options['event_name'] ) ) {
			$item_description = $this->camptix_options['event_name'];
		}

		foreach ( $order['items'] as $key => $value ) {
			$item_description .= sprintf( ', %sx %s %s', $value['quantity'], $value['name'], $value['price'] );
		}

		// Set up the return url with the tix parameters
		$return_url = add_query_arg( array(
			'tix_action'         => 'payment_return',
			'tix_payment_token'  => $payment_token,
			'tix_payment_method' => 'mercadopago',
		), $this->get_tickets_url() );

		// Set up the cancel url with the tix parameters
		$cancel_url = add_query_arg( array(
			'tix_action'         => 'payment_cancel',
			'tix_payment_token'  => $payment_token,
			'tix_payment_method' => 'mercadopago',
		), $this->get_tickets_url() );

		// Set up the order parameters
		$mercadopago_args = array(
			'back_urls' => array(
				'success' => esc_url( $return_url ),
				'failure' => str_replace( '&amp;', '&', $cancel_url ),
				'pending' => esc_url( $return_url )
			),
			'external_reference' => $payment_token,
			'notification_url'   => $return_url,
			'items' => array(
				array(
					'quantity'    => 1,
					'unit_price'  => $order['total'],
					'currency_id' => $this->camptix_options['currency'],
					'title'       => $this->camptix_options['event_name'],
					'description' => $item_description,
					'category_id' => apply_filters( 'camptix_mercadopago_category_id', 'tickets' )
				)
			)
		);

		// exclude payment types which are not instantaneous (reason: IPN)
		$types_exclude = apply_filters( 'camptix_mercadopago_exclude_payment_types', array( 'ticket', 'atm', 'bank_transfer' ) );

		if ( ! empty( $types_exclude ) ) {

			foreach ( $types_exclude as $exclude ) {
				$excludetypes[] = array( 'id' => $exclude );
			}

			$mercadopago_args['payment_methods'] = array( 'excluded_payment_types' => $excludetypes );

		}

		// let other extensions filter this
		$mercadopago_args = apply_filters( 'camptix_mercadopago_args', $mercadopago_args, $order );

		return $mercadopago_args;
	}


	/**
	 * Generate the MercadoPago payment url.
	 *
	 * @param  object $order Order Object.
	 *
	 * @return string        MercadoPago payment url.
	 */
	protected function get_mercadopago_url( $order_args ) {

		$args = wp_json_encode( $order_args );

		$url = $this->payment_url . $this->get_client_credentials();

		$params = array(
			'body'          => $args,
			'sslverify'     => true,
			'timeout'       => 60,
			'headers'       => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json;charset=UTF-8'
			)
		);

		$response = wp_remote_post( $url, $params );

		if ( ! is_wp_error( $response ) && $response['response']['code'] == 201 && ( strcmp( $response['response']['message'], 'Created' ) == 0 ) ) {
			$checkout_info = json_decode( $response['body'] );

			$this->log( __( 'Payment link generated with success from MercadoPago', 'camptix-mp' ), null, $response );

			if ( 'yes' == $this->options['sandbox'] ) {
				return esc_url( $checkout_info->sandbox_init_point );
			} else {
				return esc_url( $checkout_info->init_point );
			}

		} else {

			$this->log( __( 'Payment link response with error from MercadoPago', 'camptix-mp' ), null, $response );

		}

		return false;
	}

	/**
	 * Sets the template redirect.
	 *
	 * @return void
	 */
	public function template_redirect() {

		// Test the request.
		if ( empty( $_REQUEST['tix_payment_method'] ) || 'mercadopago' !== $_REQUEST['tix_payment_method'] )
			return;

		if ( isset( $_REQUEST['tix_action'] ) ) {

			if ( 'payment_cancel' == $_REQUEST['tix_action'] )
				$this->payment_cancel();

			if ( 'payment_return' == $_REQUEST['tix_action'] )
				$this->payment_return();

		}
	}

	/**
	 * Process the payment checkout.
	 *
	 * @param string $payment_token Payment Token.
	 *
	 * @return mixed  On success redirects to MercadoPago if fails cancels the purchase.
	 */
	public function payment_checkout( $payment_token ) {

		global $camptix;

		if ( empty( $payment_token ) ) {
			return false;
		}

		if ( ! in_array( $this->camptix_options['currency'], $this->supported_currencies ) ) {
			wp_die( __( 'The selected currency is not supported by this payment method.', 'camptix-mp' ) );
		}

		do_action( 'camptix_before_payment', $payment_token );

		// get the order args
		$mercadopago_args = $this->get_payment_args( $payment_token );

		// generate the payment URL and if everything goes well, redirect.
		if ( $mercadopago_url = $this->get_mercadopago_url( $mercadopago_args ) ) {
			wp_redirect( esc_url_raw( $mercadopago_url ) );
			die();
		} else {
			// else, trigger the Failed payment action.
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_FAILED );
		}

	}

	/**
	 * Convert payment statuses from MercadoPago responses to CampTix payment statuses.
	 *
	 * @param string $payment_status MercadoPago payment status. (collection_status)
	 *
	 * @return string CampTix payment status.
	 */
	protected function get_payment_status( $payment_status ) {

		$statuses = array(
			'approved'     => CampTix_Plugin::PAYMENT_STATUS_COMPLETED,
			'pending'      => CampTix_Plugin::PAYMENT_STATUS_PENDING,
			'in_process'   => CampTix_Plugin::PAYMENT_STATUS_PENDING,
			'rejected'     => CampTix_Plugin::PAYMENT_STATUS_CANCELLED,
			'refunded'     => CampTix_Plugin::PAYMENT_STATUS_REFUNDED,
			'cancelled'    => CampTix_Plugin::PAYMENT_STATUS_CANCELLED,
			'in_mediation' => CampTix_Plugin::PAYMENT_STATUS_PENDING,
			'charged_back' => CampTix_Plugin::PAYMENT_STATUS_REFUNDED
		);

		// Return pending for unknows statuses.
		if ( ! isset( $statuses[ $payment_status ] ) ) {
			$payment_status = 'pending';
		}

		return $statuses[ $payment_status ];
	}

	/**
	 * Process the payment return.
	 *
	 * @return void Update the order status and/or redirect to order page.
	 */
	protected function payment_return() {

		global $camptix;

		$payment_token = ( isset( $_REQUEST['tix_payment_token'] ) ) ? trim( $_REQUEST['tix_payment_token'] ) : '';

		if ( empty( $payment_token ) ) {
			$this->log( __( 'Empty token.', 'camptix-mp' ), null, $_REQUEST );
			return;
		}

		$data = $this->check_ipn_request_is_valid( $_REQUEST );

		if ( $data ) {

			$response = $data->collection;

			return $this->payment_result( $payment_token, $this->get_payment_status( $response->status ) );


		} else {
			$this->log( __( 'IPN not valid, or failed.', 'camptix-mp' ), null, $_REQUEST );
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_FAILED );
		}

	}

	/**
	 * Runs when the user cancels their payment during checkout at PayPal.
	 * his will simply tell CampTix to put the created attendee drafts into to Cancelled state.
	 */
	function payment_cancel() {

		global $camptix;

		$this->log( sprintf( 'Running payment_cancel. Request data attached.' ), null, $_REQUEST );

		$payment_token = ( isset( $_REQUEST['tix_payment_token'] ) ) ? trim( $_REQUEST['tix_payment_token'] ) : '';

		if ( ! $payment_token ) {
			$this->log( __( 'Empty token.', 'camptix-mp' ), null, $_REQUEST );
			wp_die( 'empty token' );
		}

		$attendees = get_posts( array(
			'posts_per_page' => 1,
			'post_type'      => 'tix_attendee',
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'     => 'tix_payment_token',
					'compare' => '=',
					'value'   => $payment_token,
					'type'    => 'CHAR',
				),
			),
		) );

		if ( ! $attendees ) {
			$this->log( __( 'Atendees not found.', 'camptix-mp' ), null, $_REQUEST );
			wp_die( 'attendees not found' );
		}

		// Set the associated attendees to cancelled.
		return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_CANCELLED );

	}

	/**
	 * Check IPN response
	 *
	 * @param  array $data MercadoPago post data.
	 *
	 * @return mixed       False or posted response.
	 */
	public function check_ipn_request_is_valid( $data ) {

		if ( ! isset( $data['collection_id'] ) && ! isset( $data['id'] ) ) {
			return false;
		}

		$data['collection_id'] = ! empty( $data['collection_id'] ) ? $data['collection_id'] : $data['id'];

		$this->log( __( 'Checking IPN request...', 'camptix-mp' ), null, $data );

		if ( 'yes' == $this->options['sandbox'] ) {
			$ipn_url = $this->sandbox_ipn_url;
		} else {
			$ipn_url = $this->ipn_url;
		}

		$url = $ipn_url . $data['collection_id'] . '?access_token=' . $this->get_client_credentials();

		// Send back post vars.
		$params = array(
			'sslverify' => true,
			'timeout'   => 60,
			'headers'   => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json;charset=UTF-8'
			)
		);

		// GET a response.
		$response = wp_remote_get( $url, $params );

		$this->log( __( 'IPN Response', 'camptix-mp' ), null, $response );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] ) {

			$body = json_decode( $response['body'] );

			$this->log( __( 'Received valid IPN response from MercadoPago', 'camptix-mp' ), null, $body );

			return $body;

		} else {
			$this->log( __( 'Received invalid IPN response from MercadoPago.', 'camptix-mp' ) );
		}

		return false;
	}


} // Close CampTix_Payment_Method_MercadoPago class.
endif;

/**
 * Register the Gateway in CampTix.
 */
if ( function_exists( 'camptix_register_addon' ) ) {
	camptix_register_addon( 'CampTix_Payment_Method_MercadoPago' );
}
