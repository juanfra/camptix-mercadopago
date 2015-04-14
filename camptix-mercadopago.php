<?php
/**
 * Plugin Name: CampTix MercadoPago
 * Plugin URI: https://github.com/juanfraa/camptix-mercadopago/
 * Description: MercadoPago Gateway for CampTix
 * Author: juanfra, andrezrv
 * Author URI: http://juanfra.me
 * Version: 1.0.6
 * License: GPLv2 or later
 * Text Domain: camptix-mp
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	 exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Camptix_MercadoPago' ) ) :

/**
 * Camptix MercadoPago main class.
 */
class Camptix_MercadoPago {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.6';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks that Camptix is installed.
		if ( class_exists( 'CampTix_Plugin' ) && class_exists( 'CampTix_Payment_Method' ) ) {

			// Include the MercadoPago Method class.
			include_once 'payment-mercadopago.php';

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'camptix_mercadopago_action_links' ) );

		} else {
			add_action( 'admin_notices', array( $this, 'camptix_mercadopago_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'camptix-mp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * CampTix fallback notice.
	 *
	 * @return string HTML Message.
	 */
	public function camptix_mercadopago_missing_notice() {

		$html = '<div class="error">';
			$html .= '<p>' . sprintf( __( 'CampTix MercadoPago Gateway depends on the last version of %s to work!', 'camptix-mp' ), '<a href="http://wordpress.org/extend/plugins/camptix/">CampTix</a>' ) . '</p>';
		$html .= '</div>';

		echo $html;
	}

	/**
	 * Adds custom settings url in plugins page.
	 *
	 * @param  array $links Default links.
	 *
	 * @return array	Default links and settings link.
	 */
	public function camptix_mercadopago_action_links( $links ) {

		$settings = array(
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'edit.php?post_type=tix_ticket&page=camptix_options&tix_section=payment' ),
				__( 'Settings', 'camptix-mp' )
			)
		);

		return array_merge( $settings, $links );
	}


}

add_action( 'plugins_loaded', array( 'Camptix_MercadoPago', 'get_instance' ), 0 );

endif;