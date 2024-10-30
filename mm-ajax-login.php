<?php
/**
 * Plugin Name: Mm Ajax Login
 * Plugin URI: https://wordpress.org/plugins/mm-ajax-login/
 * Description: A custom lightbox login form that serves as a gatekeeper for links with the class "ajax-login-trigger"
 * Version: 1.0.0
 * Author: MIGHTYminnow, Braad Martin
 * Author URI: http://mightyminnow.com
 * Text Domain: mm-ajax-login
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Set up some constants.
 */
define( 'MM_AJAX_LOGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MM_AJAX_LOGIN_VERSION', '1.0.0' );

add_action( 'plugins_loaded', 'mm_ajax_login_load_textdomain' );
/**
 * Load the text domain.
 */
function mm_ajax_login_load_textdomain() {
	load_plugin_textdomain( 'mm-ajax-login', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * If we're on the front end, set up our front-end hooks.
 */
if ( ! is_admin() ) {

	add_action( 'wp_enqueue_scripts', 'mm_ajax_login_enqueue_scripts' );
	add_action( 'wp_footer', 'mm_ajax_login_output_form' );
}

/**
 * Set up our ajax hooks.
 */
add_action( 'wp_ajax_mm_is_user_logged_in', 'mm_ajax_login_check_user_logged_in' );
add_action( 'wp_ajax_nopriv_mm_is_user_logged_in', 'mm_ajax_login_check_user_logged_in' );
add_action( 'wp_ajax_mm_do_ajax_login', 'mm_ajax_login_process_form_submission' );
add_action( 'wp_ajax_nopriv_mm_do_ajax_login', 'mm_ajax_login_process_form_submission' );

/**
 * Enqueue our CSS and JS.
 *
 * @since  1.0.0
 */
function mm_ajax_login_enqueue_scripts() {

	wp_enqueue_script(
		'mm-ajax-login',
		MM_AJAX_LOGIN_URL . 'js/mm-ajax-login.js',
		array( 'jquery' ),
		MM_AJAX_LOGIN_VERSION,
		true
	);

	// Allow the trigger selector to be filtered.
	$selector = apply_filters( 'mm_ajax_login_trigger_selector', '.ajax-login-trigger' );

	// Allow the initial status message to be filtered.
	$status_message = apply_filters( 'mm_ajax_login_status_message', __( 'Attempting login...', 'mm-ajax-login' ) );

	// Include a reference to the AJAX URL.
	$ajax_url = admin_url( 'admin-ajax.php' );

	// Build a single global to send to our script.
	$vars = array(
		'selector' => $selector,
		'status_message' => $status_message,
		'ajax_url' => $ajax_url,
	);

	// Send the filtered vars to our script.
	wp_localize_script(
		'mm-ajax-login',
		'mm_ajax_login_vars',
		$vars
	);

	wp_enqueue_style(
		'mm-ajax-login',
		MM_AJAX_LOGIN_URL . 'css/mm-ajax-login.css',
		array(),
		MM_AJAX_LOGIN_VERSION
	);
}

/**
 * Output the login form HTML.
 *
 * @since  1.0.0
 */
function mm_ajax_login_output_form() {

	// Allow our strings to be filtered.
	$form_title = apply_filters( 'mm_ajax_login_form_title', __( 'Site Login', 'mm-ajax-login' ) );
	$status_message = apply_filters( 'mm_ajax_login_status_message', __( 'Attempting login...', 'mm-ajax-login' ) );
	$username_label = apply_filters( 'mm_ajax_login_username_label', __( 'Username or E-mail Address:', 'mm-ajax-login' ) );
	$password_label = apply_filters( 'mm_ajax_login_password_label', __( 'Password:', 'mm-ajax-login' ) );
	$remember_me_text = apply_filters( 'mm_ajax_login_rememberme_text', __( 'Remember Me', 'mm-ajax-login' ) );
	$lost_password_text = apply_filters( 'mm_ajax_login_lost_password_text', __( 'Lost your password?', 'mm-ajax-login' ) );
	$login_button_text = apply_filters( 'mm_ajax_login_button_text', __( 'Login', 'mm-ajax-login' ) );

	?>
	<div class="mm-ajax-login">
		<div class="mm-ajax-login-inner">
			<button type="button" class="close-button">&times;</button>
			<?php do_action( 'mm_ajax_login_before_form_outside' ); ?>
			<form id="mm-ajax-login-form" action="login" method="post">
				<?php do_action( 'mm_ajax_login_before_form_inside' ); ?>
				<h1 class="mm-ajax-login-form-title"><?php echo esc_html( $form_title ); ?></h1>
				<p id="mm-ajax-login-status"><?php echo esc_html( $status_message ) ?></p>
				<p class="username">
					<label for="username"><?php echo esc_html( $username_label ); ?></label>
					<input id="mm-ajax-login-username" type="text" name="username">
				</p>
				<p class="password">
					<label for="password"><?php echo esc_html( $password_label ); ?></label>
					<input id="mm-ajax-login-password" type="password" name="password">
				</p>
				<p class="login-remember">
					<label><input id="mm-ajax-login-rememberme" name="rememberme" type="checkbox" value="forever"><?php echo esc_html( $remember_me_text ); ?></label>
				</p>
				<p class="lost-password">
					<a id="mm-ajax-login-lost-password" href="<?php echo wp_lostpassword_url(); ?>"><?php echo esc_html( $lost_password_text ); ?></a>
				</p>
				<p class="login-buttons">
					<a id="mm-ajax-login-submit-button" class="button" value="Login"><?php echo esc_html( $login_button_text ); ?></a>
					<?php do_action( 'mm_ajax_login_extra_buttons' ); ?>
				</p>
				<?php do_action( 'mm_ajax_login_after_form_inside' ); ?>
				<?php wp_nonce_field( 'mm-ajax-login-nonce' ); ?>
			</form>
			<?php do_action( 'mm_ajax_login_after_form_outside' ); ?>
		</div>
	</div>
<?php
}

/**
 * Check whether the current user is logged in.
 *
 * @since  1.0.0
 */
function mm_ajax_login_check_user_logged_in() {

	// Confirm that the nonce is there and valid, bail if it's not.
	check_ajax_referer( 'mm-ajax-login-nonce', 'nonce', true );

	if ( is_user_logged_in() ) {
		echo 'yes';
	} else {
		echo 'no';
	}

	wp_die();
}

/**
 * Process the form submission.
 *
 * @since  1.0.0
 */
function mm_ajax_login_process_form_submission() {

	// Confirm that the nonce is there and valid, bail if it's not.
	check_ajax_referer( 'mm-ajax-login-nonce', 'nonce', true );

	// Nonce is checked, allow others to use the form data in other ways (like registering a user).
	// We'll echo back whatever others return on this filter as a way to let them do a custom
	// action and return a custom message on the same filter.
	$action = apply_filters( 'mm_ajax_login_custom_login_action', 'login', $_POST );

	// Return the custom message if it is there, otherwise proceed with logging in the user.
	if ( 'login' !== $action ) {

		// Leave it up to others to pass an array that has already been run through json_encode().
		echo $action;

	} elseif ( 'login' === $action ) { // We'll do a strict check here to make debugging custom actions easier.

		$data = array();

		parse_str( $_POST['data'], $data );

		// Get the POST data we'll use for login.
		$username = $data['username'];
		$password = $data['password'];
		$remember = ( ! empty( $data['rememberme'] ) ) ? $data['rememberme'] : false;

		mm_ajax_login_log_in_user( $username, $password, $remember );
	}

	wp_die();
}

/**
 * Log in the user.
 *
 * @since  1.0.0
 */
function mm_ajax_login_log_in_user( $username, $password, $remember ) {

	// Allow our return messages to be filtered.
	$email_login_fail_message = apply_filters( 'mm_ajax_login_email_login_fail_message', __( 'The e-mail address you entered does not match a current user.', 'mm-ajax-login' ) );
	$login_success_message = apply_filters( 'mm_ajax_login_success_message', __( 'Login successful, redirecting...', 'mm-ajax-login' ) );
	$login_fail_message = apply_filters( 'mm_ajax_login_fail_message', __( 'Wrong username or password.', 'mm-ajax-login' ) );

	$info = array();

	// Allow others to disable e-mail login.
	$allow_email_login = apply_filters( 'mm_ajax_login_allow_email_login', true );

	// Check whether we're using an e-mail or username to login.
	if ( is_email( $username ) && $allow_email_login ) {

		// We have an e-mail, so attempt to get the username
		$user = get_user_by( 'email', $username );

		if ( $user ) {

			// We have a matching user, so proceed with using their username.
			$username = $user->user_login;

		} else {

			$message = esc_html( $email_login_fail_message );

			// We don't have a matching user, so return a message.
			echo json_encode( array(
				'logged_in' => false,
				'message' => $message
			) );
		}
	}

	// Build the array of login credentials.
	$info['user_login'] = $username;
	$info['user_password'] = $password;
	$info['remember'] = ( ! empty( $remember ) ) ? $remember : false;

	// Sign in the user.
	$user_signin = wp_signon( $info, true );

	// Handle success or failure.
	if ( ! is_wp_error( $user_signin ) ) {

		$message = esc_html( $login_success_message );

		// Login success.
		echo json_encode( array(
			'logged_in' => true,
			'message' => $message
		) );

	} else {

		$message = esc_html( $login_fail_message );

		// Login fail.
		echo json_encode( array(
			'logged_in' => false,
			'message' => $message
		) );
	}
}
