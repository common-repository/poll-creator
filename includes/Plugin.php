<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\Admin\Menu;

/**
 * Class Plugin.
 *
 * @package wpRigel\Pollify
 */
class Plugin {

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Plugin's url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Assets directory path.
	 *
	 * @var string
	 */
	public $assets_dir;

	/**
	 * Fire the plugin initialization step.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->path       = dirname( __DIR__, 1 );
		$this->url        = plugin_dir_url( trailingslashit( dirname( __DIR__, 1 ) ) . 'pollify.php' );
		$this->assets_dir = trailingslashit( $this->path ) . 'assets/';

		$this->load_hooks();
		$this->load();
	}

	/**
	 * Run the activator from installer
	 *
	 * @return void
	 */
	public function activator(): void {
		// phpcs:ignore;
		register_activation_hook( dirname( __FILE__, 2 ) . '/pollify.php', [ Installer::get_instance(), 'run' ] );
	}

	/**
	 * Load the plugin hooks.
	 *
	 * @return void
	 */
	public function load_hooks(): void {
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
	}

	/**
	 * Load the plugin.
	 *
	 * @return void
	 */
	public function load() {
		Assets::get_instance();

		if ( is_admin() ) {
			Menu::get_instance();
		}

		Apis::get_instance();
		Blocks::get_instance();
	}

	/**
	 * Add plugin row meta.
	 *
	 * @param array  $links Plugin row meta links.
	 * @param string $file Plugin base file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( array $links, string $file ): array {
		if ( plugin_basename( dirname( __DIR__, 1 ) . '/pollify.php' ) === $file ) {
			$row_meta = [
				'get-started' => '<a href="https://wprigel.com/pollify/" target="_blank" aria-label="' . esc_attr__( 'Get started', 'poll-creator' ) . '">' . esc_html__( 'Get started', 'poll-creator' ) . '</a>',
				'docs'        => '<a href="https://wprigel.com/docs/pollify/" target="_blank" aria-label="' . esc_attr__( 'Documentation', 'poll-creator' ) . '">' . esc_html__( 'Documentation', 'poll-creator' ) . '</a>',
			];

			return array_merge( $links, $row_meta );
		}

		return $links;
	}
}
