<?php
/**
 * Installer classe that handle functionality
 * during activation and installation.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Installer.
 *
 * @package wpRigel\Pollify
 */
class Installer {

	use Singleton;

	/**
	 * Run the installer.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->add_version();
		$this->create_tables();
	}

	/**
	 * Add version and check if the plugin is installed or not.
	 *
	 * @return void
	 */
	public function add_version(): void {
		$installed = get_option( 'pollify_installed' );

		if ( ! $installed ) {
			update_option( 'pollify_installed', time() );
		}

		update_option( 'pollify_version', POLLIFY_VERSION );
	}

	/**
	 * Create tables for the plugin.
	 *
	 * @return void
	 */
	public function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_poll_table();
		$this->create_poll_option_table();
		$this->create_poll_vote_table();
	}

	/**
	 * Create poll table.
	 *
	 * @return void
	 */
	public function create_poll_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pollify_poll';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` int NOT NULL AUTO_INCREMENT,
			`client_id` varchar(255) DEFAULT NULL,
			`title` text NOT NULL,
			`description` text DEFAULT NULL,
			`type` varchar(11) NOT NULL,
			`status` varchar(25) NOT NULL,
			`reference` text,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL,
			`settings` longtext,
			PRIMARY KEY (`id`),
			KEY `client_id` (`client_id`)
		  ) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create poll option table.
	 *
	 * @return void
	 */
	public function create_poll_option_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pollify_poll_options';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` int NOT NULL AUTO_INCREMENT,
			`poll_id` int NOT NULL,
			`option_id` varchar(255) NOT NULL,
			`type` varchar(25) NOT NULL,
			`option` longtext DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `option_id` (`option_id`)
		  ) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create poll table.
	 *
	 * @return void
	 */
	public function create_poll_vote_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pollify_vote';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` bigint NOT NULL AUTO_INCREMENT,
			`client_id` varchar(255) NOT NULL,
			`option_id` varchar(255) NOT NULL,
			`user_id` bigint DEFAULT NULL,
			`user_ip` varchar(50) DEFAULT NULL,
			`user_location` text,
			`user_state` text DEFAULT NULL,
			`user_agent` text,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `poll_id` (`client_id`,`option_id`),
			KEY `client_id` (`client_id`)
		  ) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}
}
