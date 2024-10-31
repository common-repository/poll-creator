<?php
/**
 * Menu class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Admin;

use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Menu
 */
class Menu {

	use Singleton;

	/**
	 * Load class hooks
	 *
	 * @return void
	 */
	public function __construct() {

		$this->setup_hooks();
	}

	/**
	 * Load all hooks.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Register admin menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 10 );

		// Render admin header for pollify menu.
		add_action( 'in_admin_header', [ $this, 'render_admin_header' ], 99 );

		// Enqueue scripts and styles for pollify menu.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Handle actions for pollify menu.
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
	}

	/**
	 * Check if the current page is pollify admin page.
	 *
	 * @return bool
	 */
	public function if_pollify_admin_page() {
		// Check if the page is pollify menu or not.
		global $pollify_menu;

		$screen = get_current_screen();

		return $screen->id === $pollify_menu;
	}

	/**
	 * Outputs the plugin admin header.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_header() {
		if ( ! $this->if_pollify_admin_page() ) {
			return;
		}
		?>
		<div id="wp-pollify-header-screen"></div>
		<div id="wp-pollify-header">
			<div class="logo-wrapper">
				<svg viewBox="-32 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M448 432V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48zM112 192c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h128c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h224c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h64c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16h-64z"/></svg>
				<h1>Pollify</h1>
			</div>
			<div class="quick-links">
				<a href="https://wprigel.com/contact-us" target="_blank">
					<span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Contact us', 'poll-creator' ); ?>
				</a>
				<a href="https://wprigel.com/docs" target="_blank" class="button button"><?php esc_html_e( 'Documentation', 'poll-creator' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Register menu for rendering poll related things.
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		global $pollify_menu;

		$pollify_menu = add_menu_page(
			__( 'Pollify', 'poll-creator' ),
			__( 'Pollify', 'poll-creator' ),
			'edit_posts',
			'pollify',
			[ $this, 'render_polls' ],
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'data:image/svg+xml;base64,' . base64_encode( '<svg fill="#ffffff" viewBox="-32 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M448 432V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48zM112 192c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h128c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h224c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h64c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16h-64z"/></svg>' ),
			'26'
		);

		add_action( 'load-' . $pollify_menu, [ $this, 'add_screen_option' ] );
	}

	/**
	 * Enqueue scripts and styles for pollify menu.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Check if the page is pollify menu or not.
		global $pollify_menu;

		$screen = get_current_screen();

		if ( $screen->id !== $pollify_menu ) {
			return;
		}

		// Enqueue styles and script.
		wp_enqueue_style( 'pollify-admin' );
		wp_enqueue_script( 'pollify-admin' );

		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );

		if ( ! empty( $action ) ) {
			wp_enqueue_style( 'pollify-flag-icons' );
			wp_enqueue_script( 'pollify-geo-chart' );
		}
	}

	/**
	 * Render Polls.
	 *
	 * @return void
	 */
	public function render_polls(): void {
		// Get the page.
		$page   = pollify_filter_input( INPUT_GET, 'page', POLLIFY_FILTER_SANITIZE_STRING );
		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );

		if ( 'pollify' === $page && 'view_results' === $action ) {
			$poll_id = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );

			$poll = \wpRigel\Pollify\Polls::get_instance()->get( $poll_id );

			if ( is_wp_error( $poll ) ) {
				wp_die( esc_html( $poll->get_error_message() ) );
			}

			// Load poll results template.
			pollify_load_template(
				'admin/overview.php',
				false,
				[
					'poll_id' => $poll_id,
					'poll'    => $poll,
				]
			);
		} else {
			// Load poll lists template.
			pollify_load_template( 'admin/polls.php' );
		}
	}

	/**
	 * Add screen option for polls.
	 *
	 * @return void
	 */
	public function add_screen_option(): void {
		global $pollify_menu;

		$screen = get_current_screen();

		// Bail out of here if we are not on our pollify page.
		if ( ! is_object( $screen ) || $screen->id !== $pollify_menu ) {
			return;
		}

		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );

		// Check if has action and the value is view_results or reset_results. Then return.
		if ( $action && in_array( $action, [ 'view_results', 'reset_results' ], true ) ) {
			return;
		}

		$args = [
			'label'   => __( 'Polls per page', 'poll-creator' ),
			'default' => 10,
			'option'  => 'polls_per_page',
		];

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Handle actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		$page   = pollify_filter_input( INPUT_GET, 'page', POLLIFY_FILTER_SANITIZE_STRING );
		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );
		$nonce  = pollify_filter_input( INPUT_GET, '_nonce', POLLIFY_FILTER_SANITIZE_STRING );

		if ( 'pollify' !== $page || empty( $action ) ) {
			return;
		}

		if ( 'reset_results' === $action && wp_verify_nonce( $nonce, 'pollify_reset_results' ) ) {
			$client_id = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );

			if ( ! empty( $client_id ) ) {
				\wpRigel\Pollify\Votes::get_instance()->reset_results( $client_id );

				// Reset cache for the poll.
				if ( wp_cache_supports( 'flush_group' ) ) {
					wp_cache_flush_group( 'pollify_poll_cache' );
				}

				wp_safe_redirect( admin_url( 'admin.php?page=pollify&updated=1' ) );
			}
		}
	}
}
