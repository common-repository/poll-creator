<?php
/**
 * Plugin Name: Pollify
 * Plugin URI: http://wprigel.com/product/poll-creator/
 * Description: Pollify is the ultimate poll creator and survey maker plugin for WordPress, 100% powered by the Gutenberg editor. No short code required, no capping on vote counts. Enjoy the freedom & boost user engagement.
 * Version: 1.0.2
 * Author: wprigel
 * Author URI: http://wprigel.com/
 * License: GPL2
 * Text Domain: poll-creator
 *
 * @package wpRigel\Pollify
 */

/**
 * Copyright (c) YEAR WPRigel (email: info@wprigel.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

// don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define some constant for getting path and urls and version of the plugin.
define( 'POLLIFY_VERSION', '1.0.2' );
define( 'POLLIFY_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'POLLIFY_ASSET_PATH', untrailingslashit( POLLIFY_PATH . '/assets' ) );
define( 'POLLIFY_ASSET_BUILD_PATH', untrailingslashit( POLLIFY_PATH . '/assets/build' ) );
define( 'POLLIFY_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'POLLIFY_ASSET_URL', untrailingslashit( POLLIFY_URL . '/assets' ) );
define( 'POLLIFY_ASSET_BUILD_URL', untrailingslashit( POLLIFY_URL . '/assets/build' ) );
define( 'POLLIFY_FILTER_SANITIZE_STRING', 'filter-sanitize-string' );

/**
 * Autoload the dependencies.
 *
 * @return bool
 */
function autoload(): bool {
	static $loaded;

	if ( wp_validate_boolean( $loaded ) ) {
		return $loaded;
	}

	$autoload_file = __DIR__ . '/vendor/autoload.php';

	if ( file_exists( $autoload_file ) && is_readable( $autoload_file ) ) {
		require_once $autoload_file;
		$loaded = true;
		return $loaded;
	}

	$loaded = false;
	return $loaded;
}

/**
 * Don't load anything if composer autoload
 * not loaded.
 */
if ( ! autoload() ) {
	return;
}

// Load all common helper functions.
require_once POLLIFY_PATH . '/includes/helpers/functions.php';

/**
 * Get the main Plugin instance.
 *
 * @return Plugin
 */
function plugin(): Plugin {
	static $plugin;

	if ( null !== $plugin ) {
		return $plugin;
	}

	$plugin = new Plugin();

	return $plugin;
}

/**
 * Initialize the plugin.
 */
add_action(
	'plugins_loaded',
	function () {
		plugin()->run();
	}
);

/**
 * Run when plugin is activated
 */
plugin()->activator();
