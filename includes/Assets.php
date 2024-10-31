<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Pollify
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\Traits\Singleton;

/**
 * Assets class.
 *
 * @package wpRigel\Pollify
 */
class Assets {

	use Singleton;

	/**
	 * Contructor function.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Load all hooks.
	 *
	 * @return void
	 */
	public function admin_scripts() {
		$this->register_style( 'pollify-flag-icons', 'libs/flag-icon.css' );
		$this->register_script( 'pollify-geo-chart', 'libs/gstatic-loader.js', [], POLLIFY_VERSION, false );
		$this->register_script( 'pollify-admin', 'build/admin.js', [ 'pollify-geo-chart' ] );
		$this->register_style( 'pollify-admin', 'build/admin.css' );
	}

	/**
	 * Load dependencies and version info from {handle}.asset.php if exists.
	 *
	 * @param string $file File name.
	 * @param array  $deps Script dependencies to merge with.
	 * @param string $ver  Asset version string.
	 *
	 * @return array
	 */
	public function get_asset_meta( $file, $deps = [], $ver = false ) {
		$asset_meta_file = sprintf( '%s/%s.asset.php', untrailingslashit( POLLIFY_ASSET_BUILD_PATH ), basename( $file, '.' . pathinfo( $file )['extension'] ) );
		$asset_meta      = is_readable( $asset_meta_file )
			? require $asset_meta_file
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $file, $ver ),
			];

		$asset_meta['dependencies'] = array_merge( $deps, $asset_meta['dependencies'] );

		return $asset_meta;
	}

	/**
	 * Get file version.
	 *
	 * @param string             $file File path.
	 * @param int|string|boolean $ver  File version.
	 *
	 * @return bool|false|int
	 */
	public function get_file_version( $file, $ver = false ) {
		if ( ! empty( $ver ) ) {
			return $ver;
		}

		$file_path = sprintf( '%s/%s', POLLIFY_ASSET_BUILD_PATH, $file );

		return file_exists( $file_path ) ? filemtime( $file_path ) : false;
	}


	/**
	 * Register a new script.
	 *
	 * @param string           $handle    Name of the script. Should be unique.
	 * @param string|bool      $file       script file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps      Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param string|bool|null $ver       Optional. String specifying script version number, if not set, filetime will be used as version number.
	 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                    Default 'false'.
	 * @return bool Whether the script has been registered. True on success, false on failure.
	 */
	public function register_script( $handle, $file, $deps = [], $ver = false, $in_footer = true ) {
		$src        = ( false !== strpos( $file, 'https://' ) ) ? $file : sprintf( POLLIFY_ASSET_URL . '/%s', $file );
		$asset_meta = $this->get_asset_meta( $file, $deps, $ver );

		return wp_register_script( $handle, $src, $asset_meta['dependencies'], $asset_meta['version'], $in_footer );
	}

	/**
	 * Register a CSS stylesheet.
	 *
	 * @param string           $handle Name of the stylesheet. Should be unique.
	 * @param string|bool      $file    style file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying script version number, if not set, filetime will be used as version number.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 *
	 * @return bool Whether the style has been registered. True on success, false on failure.
	 */
	public function register_style( $handle, $file, $deps = [], $ver = false, $media = 'all' ) {
		// Check if $src string contain https and any domain name then skip.
		if ( false !== strpos( $file, 'https://' ) ) {
			$src = $file;
			$ver = POLLIFY_VERSION;
		} else {
			$src = sprintf( POLLIFY_ASSET_URL . '/%s', $file );
			$ver = $this->get_file_version( $file, $ver );
		}

		return wp_register_style( $handle, $src, $deps, $ver, $media );
	}
}
