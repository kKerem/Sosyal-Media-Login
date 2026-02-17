<?php
/*
Plugin Name: kKerem - Sosyal Medya İle Giriş Yap
Plugin URI: https://kkerem.com/projeler/bildirim-sistemi
Description: WooCommerce için sosyal medya ile giriş. Google ile giriş örnek sağlayıcı ve daha fazlası için genişletilebilir yapı.
Version: 0.1.0
Author: kKerem
Author URI: http://kkerem.com
License: GPLv2 or later
Text Domain: kkerem-social-login
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KKEREM_SL_VERSION', '0.1.0' );
define( 'KKEREM_SL_FILE', __FILE__ );
define( 'KKEREM_SL_DIR', plugin_dir_path( __FILE__ ) );
define( 'KKEREM_SL_URL', plugin_dir_url( __FILE__ ) );

// Loader
require_once KKEREM_SL_DIR . 'includes/class-kkerem-social-login.php';

// Bootstrap
add_action( 'plugins_loaded', function () {
	kKerem_Social_Login::instance();
} );

register_activation_hook( __FILE__, function () {
	// Initialize default settings on first install
	$option_key = 'kkerem_social_login_settings';
	$defaults = array(
		'providers' => array(
			'google' => array(
				'enabled' => false,
				'client_id' => '',
				'client_secret' => '',
			),
			'facebook' => array(
				'enabled' => false,
				'app_id' => '',
				'app_secret' => '',
			),
			'apple' => array(
				'enabled' => false,
				'client_id' => '',
				'team_id' => '',
				'key_id' => '',
				'private_key' => '',
			),
			'twitter' => array(
				'enabled' => false,
				'client_id' => '',
				'client_secret' => '',
			),
			'github' => array(
				'enabled' => false,
				'client_id' => '',
				'client_secret' => '',
			),
			'linkedin' => array(
				'enabled' => false,
				'client_id' => '',
				'client_secret' => '',
			),
		),
		'ui' => array(
			'button_style' => 'default',
			'show_on_login_form' => true,
			'show_on_register_form' => true,
		),
	);

	if ( get_option( $option_key ) === false ) {
		add_option( $option_key, $defaults );
	}
} );

