<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Automator
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Api.
 */
class Apis {

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
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ], 10 );
	}

	/**
	 * Register all classes for load rest routes
	 *
	 * @return array
	 */
	public function map_classes(): array {
		return [
			\wpRigel\Pollify\REST\PollsController::class,
			\wpRigel\Pollify\REST\VotesController::class,
		];
	}

	/**
	 * Register all rest routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		foreach ( $this->map_classes() as $class ) {
			if ( class_exists( $class ) ) {
				$class_instance = new $class();

				if ( method_exists( $class_instance, 'register_routes' ) ) {
					$class_instance->register_routes();
				}
			}
		}

		/**
		 * Do other stuffs during rest api int.
		 */
		do_action( 'pollify_rest_api_init' );
	}
}
