<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class kKerem_Social_Login {
	private static $instance = null;
	private $option_key = 'kkerem_social_login_settings';

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Frontend: shortcode and WooCommerce hooks
		add_shortcode( 'kkerem_social_login', array( $this, 'shortcode_buttons' ) );
		add_action( 'woocommerce_login_form_end', array( $this, 'render_buttons_if_enabled_on_login' ) );
		add_action( 'woocommerce_register_form_end', array( $this, 'render_buttons_if_enabled_on_register' ) );

		// Frontend CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Routing for OAuth start and callback
		add_action( 'init', array( $this, 'maybe_handle_oauth_routes' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'kkerem-social-login' ) === false ) {
			return;
		}
		wp_enqueue_style( 'kkerem-sl-admin', KKEREM_SL_URL . 'assets/css/admin.css', array(), KKEREM_SL_VERSION );
		wp_enqueue_script( 'kkerem-sl-admin', KKEREM_SL_URL . 'assets/js/admin.js', array( 'jquery' ), KKEREM_SL_VERSION, true );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'kkerem-sl-frontend', KKEREM_SL_URL . 'assets/css/admin.css', array(), KKEREM_SL_VERSION );
	}

	public function render_buttons_if_enabled_on_login() {
		$settings = $this->get_settings();
		if ( ! empty( $settings['ui']['show_on_login_form'] ) ) {
			$this->render_buttons();
		}
	}

	public function render_buttons_if_enabled_on_register() {
		$settings = $this->get_settings();
		if ( ! empty( $settings['ui']['show_on_register_form'] ) ) {
			$this->render_buttons();
		}
	}

	public function shortcode_buttons() {
		ob_start();
		$this->render_buttons();
		return ob_get_clean();
	}

	private function render_buttons() {
		$settings = $this->get_settings();
		$providers = $this->get_providers_schema();
		$enabled = array();
		foreach ( $providers as $key => $schema ) {
			if ( ! empty( $settings['providers'][ $key ]['enabled'] ) ) {
				$enabled[] = $key;
			}
		}
		if ( empty( $enabled ) ) {
			return;
		}

		$style = isset( $settings['ui']['button_style'] ) ? $settings['ui']['button_style'] : 'default';
		echo '<div class="d-flex align-items-center my-4">
            <hr class="w-100 m-0">
            <span class="text-body-emphasis fw-medium text-nowrap mx-4">' . esc_html__( "yada", "woocommerce" ) . '</span>
            <hr class="w-100 m-0">
          </div>';	
		echo '<div class="kkerem-sl-buttons kkerem-sl-style-' . esc_attr( $style ) . '">';
		foreach ( $enabled as $provider_key ) {
			$url = $this->get_oauth_start_url( $provider_key );
			$provider_name = ucfirst( $provider_key );
			$icon_class = $this->get_provider_icon_class( $provider_key );
			$button_text = sprintf( __( '%s ile giriş yap', 'kkerem-social-login' ), $provider_name );
			
			echo '<div class="mb-2">';
			echo '<a href="' . esc_url( $url ) . '" class="btn btn-lg btn-outline-secondary my-3 d-block fs-sm">';
			echo '<i class="' . esc_attr( $icon_class ) . ' me-3"></i>';
			echo esc_html( $button_text );
			echo '</a>';
			echo '</div>';
		}
		echo '</div>';
	}

	private function get_provider_icon_class( $provider_key ) {
		$icons = array(
			'google' => 'ci-google',
			'facebook' => 'ci-facebook',
			'apple' => 'ci-apple',
			'twitter' => 'ci-twitter',
			'github' => 'ci-github',
			'linkedin' => 'ci-linkedin',
		);
		
		return isset( $icons[ $provider_key ] ) ? $icons[ $provider_key ] : 'ci-user';
	}

	private function get_oauth_start_url( $provider_key ) {
		return add_query_arg( array( 'kkerem-sl-login' => $provider_key ), wp_login_url() );
	}

	public function maybe_handle_oauth_routes() {
		if ( isset( $_GET['kkerem-sl-login'] ) ) {
			$provider = sanitize_key( wp_unslash( $_GET['kkerem-sl-login'] ) );
			$this->handle_oauth_start( $provider );
			exit;
		}
		if ( isset( $_GET['kkerem-sl-callback'] ) ) {
			$provider = sanitize_key( wp_unslash( $_GET['kkerem-sl-callback'] ) );
			$this->handle_oauth_callback( $provider );
			exit;
		}
	}

	private function handle_oauth_start( $provider ) {
		if ( $provider === 'google' ) {
			$this->google_oauth_start();
			return;
		}
		wp_die( esc_html__( 'Desteklenmeyen sağlayıcı.', 'kkerem-social-login' ) );
	}

	private function handle_oauth_callback( $provider ) {
		if ( $provider === 'google' ) {
			$this->google_oauth_callback();
			return;
		}
		wp_die( esc_html__( 'Desteklenmeyen sağlayıcı callback.', 'kkerem-social-login' ) );
	}

	private function google_oauth_start() {
		$settings = $this->get_settings();
		$conf = isset( $settings['providers']['google'] ) ? $settings['providers']['google'] : array();
		if ( empty( $conf['enabled'] ) || empty( $conf['client_id'] ) || empty( $conf['client_secret'] ) ) {
			wp_die( esc_html__( 'Google sağlayıcı yapılandırılmamış.', 'kkerem-social-login' ) );
		}
		$client_id = $conf['client_id'];
		$redirect_uri = add_query_arg( array( 'kkerem-sl-callback' => 'google' ), wp_login_url() );
		$scope = rawurlencode( 'openid email profile' );
		$state = wp_generate_password( 20, false );
		set_transient( 'kkerem_sl_state_' . $state, time(), 15 * MINUTE_IN_SECONDS );
		$args = array(
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
			'response_type' => 'code',
			'scope' => 'openid email profile',
			'state' => $state,
			'access_type' => 'offline',
			'prompt' => 'consent',
		);
		$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $args, '', '&' );
		wp_redirect( $auth_url );
	}

	private function google_oauth_callback() {
		if ( isset( $_GET['error'] ) ) {
			wp_die( esc_html( 'Google OAuth hata: ' . sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) );
		}
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		if ( empty( $code ) || empty( $state ) || ! get_transient( 'kkerem_sl_state_' . $state ) ) {
			wp_die( esc_html__( 'Geçersiz istek.', 'kkerem-social-login' ) );
		}
		delete_transient( 'kkerem_sl_state_' . $state );

		$settings = $this->get_settings();
		$conf = isset( $settings['providers']['google'] ) ? $settings['providers']['google'] : array();
		$client_id = $conf['client_id'];
		$client_secret = $conf['client_secret'];
		$redirect_uri = add_query_arg( array( 'kkerem-sl-callback' => 'google' ), wp_login_url() );

		$token_resp = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
			'body' => http_build_query( array(
				'code' => $code,
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri' => $redirect_uri,
				'grant_type' => 'authorization_code',
			), '', '&' ),
			'timeout' => 20,
		) );
		if ( is_wp_error( $token_resp ) ) {
			wp_die( esc_html__( 'Token isteği başarısız.', 'kkerem-social-login' ) );
		}
		$code_resp = wp_remote_retrieve_body( $token_resp );
		$data = json_decode( $code_resp, true );
		$access_token = isset( $data['access_token'] ) ? $data['access_token'] : '';
		$id_token = isset( $data['id_token'] ) ? $data['id_token'] : '';
		if ( empty( $access_token ) ) {
			wp_die( esc_html__( 'Erişim anahtarı alınamadı.', 'kkerem-social-login' ) );
		}

		// Fetch user info
		$user_resp = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/userinfo', array(
			'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			'timeout' => 20,
		) );
		if ( is_wp_error( $user_resp ) ) {
			wp_die( esc_html__( 'Kullanıcı bilgisi alınamadı.', 'kkerem-social-login' ) );
		}
		$user_data = json_decode( wp_remote_retrieve_body( $user_resp ), true );
		$email = isset( $user_data['email'] ) ? sanitize_email( $user_data['email'] ) : '';
		$name = isset( $user_data['name'] ) ? sanitize_text_field( $user_data['name'] ) : '';
		$sub = isset( $user_data['sub'] ) ? sanitize_text_field( $user_data['sub'] ) : '';
		if ( empty( $email ) ) {
			wp_die( esc_html__( 'E-posta gerekli.', 'kkerem-social-login' ) );
		}

		// Login or create user
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$username_base = sanitize_user( current( explode( '@', $email ) ) );
			$username = $username_base;
			$counter = 1;
			while ( username_exists( $username ) ) {
				$username = $username_base . $counter;
				$counter++;
			}
			
			// Create user first with subscriber role (default)
			$user_id = wp_create_user( $username, wp_generate_password( 20 ), $email );
			
			if ( is_wp_error( $user_id ) ) {
				wp_die( esc_html__( 'Kullanıcı oluşturulamadı: ' . $user_id->get_error_message(), 'kkerem-social-login' ) );
			}
			
			// Update display name
			wp_update_user( array( 'ID' => $user_id, 'display_name' => $name ) );
			
			// Force customer role assignment
			$user_obj = new WP_User( $user_id );
			$user_obj->remove_all_caps();
			$user_obj->set_role( 'customer' );
			
			// Additional WooCommerce customer data
			update_user_meta( $user_id, 'billing_email', $email );
			update_user_meta( $user_id, 'billing_first_name', $name );
			update_user_meta( $user_id, 'billing_last_name', '' );
			
			// Debug: Log the user creation
			error_log( 'Social Login: User created with ID ' . $user_id . ' and roles: ' . implode( ', ', $user_obj->roles ) );
			
			$user = get_user_by( 'id', $user_id );
		} else {
			// If user exists but doesn't have customer role, update it
			$user_obj = new WP_User( $user->ID );
			if ( ! in_array( 'customer', $user_obj->roles ) ) {
				$user_obj->set_role( 'customer' );
				error_log( 'Social Login: Updated existing user ' . $user->ID . ' to customer role' );
			}
		}

		// Sign in
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
		do_action( 'wp_login', $user->user_login, $user );

		// Ensure customer role is set after login (in case WooCommerce wasn't fully loaded)
		add_action( 'wp_loaded', function() use ( $user ) {
			$user_obj = new WP_User( $user->ID );
			if ( ! in_array( 'customer', $user_obj->roles ) ) {
				$user_obj->set_role( 'customer' );
				error_log( 'Social Login: Fixed role after wp_loaded for user ' . $user->ID );
			}
		}, 20 );

		$redirect = wc_get_page_permalink( 'myaccount' );
		if ( ! $redirect ) {
			$redirect = admin_url();
		}
		wp_safe_redirect( $redirect );
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'Sosyal Giriş', 'kkerem-social-login' ),
			__( 'Sosyal Giriş', 'kkerem-social-login' ),
			'manage_options',
			'kkerem-social-login',
			array( $this, 'render_settings_page' ),
			'dashicons-share'
		);
	}

	public function register_settings() {
		register_setting( 'kkerem_sl_settings', $this->option_key, array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'kkerem_sl_section_providers',
			__( 'Sağlayıcılar', 'kkerem-social-login' ),
			'__return_false',
			'kkerem-social-login'
		);

		// Provider fields: Google, Facebook, Apple, Twitter, GitHub, LinkedIn
		$providers = $this->get_providers_schema();
		foreach ( $providers as $provider_key => $provider ) {
			add_settings_field(
				"{$provider_key}_fieldset",
				sprintf( __( '%s', 'kkerem-social-login' ), $provider['label'] ),
				array( $this, 'render_provider_fieldset' ),
				'kkerem-social-login',
				'kkerem_sl_section_providers',
				array( 'provider_key' => $provider_key, 'provider' => $provider )
			);
		}

		add_settings_section(
			'kkerem_sl_section_ui',
			__( 'Arayüz', 'kkerem-social-login' ),
			'__return_false',
			'kkerem-social-login'
		);
		add_settings_field(
			'ui_options',
			__( 'Buton Ayarları', 'kkerem-social-login' ),
			array( $this, 'render_ui_options' ),
			'kkerem-social-login',
			'kkerem_sl_section_ui'
		);
	}

	public function sanitize_settings( $input ) {
		$defaults = get_option( $this->option_key, array() );
		$output = $defaults;

		if ( isset( $input['providers'] ) && is_array( $input['providers'] ) ) {
			$schema = $this->get_providers_schema();
			foreach ( $schema as $key => $provider ) {
				if ( isset( $input['providers'][ $key ] ) ) {
					$incoming = $input['providers'][ $key ];
					$output['providers'][ $key ]['enabled'] = ! empty( $incoming['enabled'] );
					foreach ( $provider['fields'] as $field_key => $field_label ) {
						$value = isset( $incoming[ $field_key ] ) ? trim( wp_unslash( $incoming[ $field_key ] ) ) : '';
						$output['providers'][ $key ][ $field_key ] = $value;
					}
				}
			}
		}

		if ( isset( $input['ui'] ) ) {
			$output['ui']['button_style'] = isset( $input['ui']['button_style'] ) ? sanitize_key( $input['ui']['button_style'] ) : 'default';
			$output['ui']['show_on_login_form'] = ! empty( $input['ui']['show_on_login_form'] );
			$output['ui']['show_on_register_form'] = ! empty( $input['ui']['show_on_register_form'] );
		}

		return $output;
	}

	private function get_settings() {
		return get_option( $this->option_key, array() );
	}

	private function get_providers_schema() {
		return array(
			'google' => array(
				'label' => 'Google',
				'fields' => array(
					'client_id' => 'Client ID',
					'client_secret' => 'Client Secret',
				),
			),
			'facebook' => array(
				'label' => 'Facebook',
				'fields' => array(
					'app_id' => 'App ID',
					'app_secret' => 'App Secret',
				),
			),
			'apple' => array(
				'label' => 'Apple',
				'fields' => array(
					'client_id' => 'Client ID',
					'team_id' => 'Team ID',
					'key_id' => 'Key ID',
					'private_key' => 'Private Key',
				),
			),
			'twitter' => array(
				'label' => 'Twitter/X',
				'fields' => array(
					'client_id' => 'Client ID',
					'client_secret' => 'Client Secret',
				),
			),
			'github' => array(
				'label' => 'GitHub',
				'fields' => array(
					'client_id' => 'Client ID',
					'client_secret' => 'Client Secret',
				),
			),
			'linkedin' => array(
				'label' => 'LinkedIn',
				'fields' => array(
					'client_id' => 'Client ID',
					'client_secret' => 'Client Secret',
				),
			),
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Handle fix user roles action
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'fix_user_roles' ) {
			$this->fix_existing_user_roles();
		}
		
		$settings = $this->get_settings();
		$providers = $this->get_providers_schema();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'kKerem - Sosyal Medya İle Giriş Yap', 'kkerem-social-login' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'kkerem_sl_settings' ); ?>
				<div class="kkerem-sl-sections">
					<h2 class="title"><?php esc_html_e( 'Sağlayıcı Ayarları', 'kkerem-social-login' ); ?></h2>
					<?php do_settings_sections( 'kkerem-social-login' ); ?>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function render_provider_fieldset( $args ) {
		$provider_key = $args['provider_key'];
		$provider = $args['provider'];
		$settings = $this->get_settings();
		$current = isset( $settings['providers'][ $provider_key ] ) ? $settings['providers'][ $provider_key ] : array();
		$is_enabled = ! empty( $current['enabled'] );
		?>
		<fieldset class="kkerem-sl-provider">
			<label>
				<input type="checkbox" 
					   class="provider-toggle" 
					   data-provider="<?php echo esc_attr( $provider_key ); ?>"
					   name="<?php echo esc_attr( $this->option_key ); ?>[providers][<?php echo esc_attr( $provider_key ); ?>][enabled]" 
					   value="1" 
					   <?php checked( $is_enabled ); ?> />
				<?php echo esc_html( $provider['label'] ); ?> <?php esc_html_e( 'aktif', 'kkerem-social-login' ); ?>
			</label>
			<div class="kkerem-sl-provider-fields" id="<?php echo esc_attr( $provider_key . '_fields' ); ?>" style="<?php echo $is_enabled ? '' : 'display: none;'; ?>">
				<?php foreach ( $provider['fields'] as $field_key => $field_label ) :
					$value = isset( $current[ $field_key ] ) ? $current[ $field_key ] : '';
					?>
					<p>
						<label for="<?php echo esc_attr( $provider_key . '_' . $field_key ); ?>"><strong><?php echo esc_html( $field_label ); ?></strong></label><br />
						<input type="text" id="<?php echo esc_attr( $provider_key . '_' . $field_key ); ?>" name="<?php echo esc_attr( $this->option_key ); ?>[providers][<?php echo esc_attr( $provider_key ); ?>][<?php echo esc_attr( $field_key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
					</p>
				<?php endforeach; ?>
				<p class="description">
					<?php esc_html_e( 'Dönüş (redirect) URL adresinizi sağlayıcı panelinde aşağıdaki gibi ayarlayın:', 'kkerem-social-login' ); ?><br />
					<code><?php echo esc_html( wp_login_url() ); ?>?kkerem-sl-callback=<?php echo esc_html( $provider_key ); ?></code>
				</p>
			</div>
		</fieldset>
		<?php
	}

	public function render_ui_options() {
		$settings = $this->get_settings();
		$ui = isset( $settings['ui'] ) ? $settings['ui'] : array();
		$button_style = isset( $ui['button_style'] ) ? $ui['button_style'] : 'default';
		$show_on_login = ! empty( $ui['show_on_login_form'] );
		$show_on_register = ! empty( $ui['show_on_register_form'] );
		?>
		<p>
			<label for="kkerem-sl-button-style"><strong><?php esc_html_e( 'Buton Stili', 'kkerem-social-login' ); ?></strong></label><br />
			<select id="kkerem-sl-button-style" name="<?php echo esc_attr( $this->option_key ); ?>[ui][button_style]">
				<option value="default" <?php selected( $button_style, 'default' ); ?>>Default</option>
				<option value="minimal" <?php selected( $button_style, 'minimal' ); ?>>Minimal</option>
				<option value="rounded" <?php selected( $button_style, 'rounded' ); ?>>Rounded</option>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[ui][show_on_login_form]" value="1" <?php checked( $show_on_login ); ?> />
				<?php esc_html_e( 'WooCommerce giriş formunda göster', 'kkerem-social-login' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[ui][show_on_register_form]" value="1" <?php checked( $show_on_register ); ?> />
				<?php esc_html_e( 'WooCommerce kayıt formunda göster', 'kkerem-social-login' ); ?>
			</label>
		</p>
		<p>
			<button type="button" class="button" onclick="if(confirm('Bu işlem sosyal medya ile giriş yapmış tüm kullanıcıları müşteri rolüne çevirecek. Devam etmek istediğinizden emin misiniz?')) { window.location.href='<?php echo admin_url( 'admin.php?page=kkerem-social-login&action=fix_user_roles' ); ?>'; }">
				<?php esc_html_e( 'Mevcut Kullanıcıları Müşteri Rolüne Çevir', 'kkerem-social-login' ); ?>
			</button>
			<br><small class="description"><?php esc_html_e( 'Sosyal medya ile giriş yapmış kullanıcıların rollerini düzeltir.', 'kkerem-social-login' ); ?></small>
		</p>
		<?php
	}

	private function fix_existing_user_roles() {
		// Find all users with subscriber role
		$users = get_users( array(
			'role' => 'subscriber',
			'number' => -1
		) );

		$fixed_count = 0;
		foreach ( $users as $user ) {
			// Check if this user has social login indicators or billing data
			$billing_email = get_user_meta( $user->ID, 'billing_email', true );
			$billing_first_name = get_user_meta( $user->ID, 'billing_first_name', true );
			$user_email = $user->user_email;
			
			// Convert to customer if they have billing data or if their email matches billing email
			if ( ( $billing_email && $billing_first_name ) || 
				 ( $billing_email && $billing_email === $user_email ) ||
				 ( $billing_first_name && $billing_first_name !== '' ) ) {
				
				$user_obj = new WP_User( $user->ID );
				$user_obj->remove_all_caps();
				$user_obj->set_role( 'customer' );
				$fixed_count++;
				
				error_log( 'Fixed user role for user ID: ' . $user->ID . ' (email: ' . $user_email . ')' );
			}
		}

		// Also check for any users with empty roles or wrong roles
		$all_users = get_users( array( 'number' => -1 ) );
		foreach ( $all_users as $user ) {
			$user_obj = new WP_User( $user->ID );
			$billing_email = get_user_meta( $user->ID, 'billing_email', true );
			
			// If user has billing email but not customer role, fix it
			if ( $billing_email && $billing_email === $user->user_email && ! in_array( 'customer', $user_obj->roles ) ) {
				$user_obj->set_role( 'customer' );
				$fixed_count++;
				error_log( 'Fixed user role for user ID: ' . $user->ID . ' (had billing email but wrong role)' );
			}
		}

		echo '<div class="notice notice-success"><p>';
		printf( esc_html__( '%d kullanıcı müşteri rolüne çevrildi.', 'kkerem-social-login' ), $fixed_count );
		echo '</p></div>';
	}
}


