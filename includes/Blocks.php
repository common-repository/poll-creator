<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\Model\Poll;
use wpRigel\Pollify\Polls;
use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Plugin.
 *
 * @package wpRigel\Pollify
 */
class Blocks {

	use Singleton;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_blocks' ] );
		add_action( 'block_categories_all', [ $this, 'register_block_category' ] );
		add_action( 'init', [ $this, 'register_block_styles' ] );
		add_action( 'save_post', [ $this, 'save_polls' ], 10, 2 );

		// Add localize script for nonces.
		add_action( 'wp_enqueue_scripts', [ $this, 'localize_script' ] );
	}

	/**
	 * Initialize blocks.
	 */
	public function init_blocks() {
		register_block_type(
			POLLIFY_PATH . '/build/poll',
			array(
				'render_callback' => [ $this, 'render_block' ],
			)
		);
	}

	/**
	 * Register block category.
	 *
	 * @param array $categories Block categories.
	 *
	 * @return array
	 */
	public function register_block_category( $categories ): array {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'pollify',
					'title' => __( 'Pollify', 'poll-creator' ),
				),
			)
		);
	}

	/**
	 * Render block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string|null
	 */
	public function render_block( $attributes ): ?string {
		$poll_client_id = $attributes['pollClientId'] ?? 0;

		if ( empty( $poll_client_id ) ) {
			return null;
		}

		ob_start();
		include plugin()->path . '/templates/poll/poll.php';
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Save polls.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_polls( $post_id, $post ) {
		if (
			wp_is_post_autosave( $post_id )
			|| wp_is_post_revision( $post_id )
			|| 'trash' === $post->post_status
			|| 'auto-draft' === $post->post_status
		) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );

		$polls = array_filter(
			$blocks,
			function ( $block ) {
				return 'pollify/poll' === $block['blockName'];
			}
		);

		if ( empty( $polls ) ) {
			$saved_poll_ids = get_post_meta( $post_id, '_pollify_poll_client_ids', true );

			// Checked if saved poll ids are not empty.
			if ( ! empty( $saved_poll_ids ) ) {
				// Loop through all saved poll ids and delete them.
				// since there are no polls avaialbe in post.
				foreach ( $saved_poll_ids as $saved_poll_id ) {
					Polls::get_instance()->delete( $saved_poll_id );
				}
			}

			// Delete poll client ids.
			delete_post_meta( $post_id, '_pollify_poll_client_ids' );

			return;
		}

		// Get all attributes and update the poll.
		foreach ( $polls as $poll ) {
			$poll_client_id = $poll['attrs']['pollClientId'] ?? '';

			if ( empty( $poll_client_id ) ) {
				continue;
			}

			$data              = $poll['attrs'] ?? [];
			$data['client_id'] = $poll_client_id;
			$skipped_field     = [ 'pollId', 'pollClientId', 'options', 'title', 'description', 'style' ];

			unset(
				$poll['attrs']['pollId'],
				$poll['attrs']['pollClientId'],
				$poll['attrs']['options'],
				$poll['attrs']['title'],
				$poll['attrs']['description'],
				$poll['attrs']['style']
			);

			/**
			 * We use file_get_contents here because we need to get the block.json file from the build folder.
			 * This is a safe operation because we are not fetching any external content.
			 */
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$json             = file_get_contents( plugin()->path . '/build/poll/block.json' );
			$json_data        = json_decode( $json, true );
			$block_attributes = $json_data['attributes'] ?? [];

			// Loop through all block attributes and check if it's not set then set it to default value.
			foreach ( $block_attributes as $key => $value ) {
				if ( ! in_array( $key, $skipped_field, true ) && ! isset( $poll['attrs'][ $key ] ) ) {
					$poll['attrs'][ $key ] = $value['default'] ?? '';
				}
			}

			$data['reference'] = $post_id;
			$data['settings']  = serialize_block_attributes( $poll['attrs'] );

			Polls::get_instance()->save( $data );
		}

		$poll_ids = array_map(
			function ( $poll ) {
				return $poll['attrs']['pollClientId'];
			},
			$polls
		);

		// Check if poll id is not in saved meta, then delete it.
		$saved_poll_ids = get_post_meta( $post_id, '_pollify_poll_client_ids', true );

		if ( ! empty( $saved_poll_ids ) ) {
			foreach ( $saved_poll_ids as $saved_poll_id ) {
				if ( ! in_array( $saved_poll_id, $poll_ids, true ) ) {
					Polls::get_instance()->delete( $saved_poll_id );
				}
			}
		}

		update_post_meta( $post_id, '_pollify_poll_client_ids', $poll_ids );
	}

	/**
	 * Register block styles.
	 */
	public function register_block_styles() {
		$block_styles = [
			[
				'block' => 'pollify/poll',
				'style' => [
					'name'  => 'poll-inline-list',
					'label' => __( 'Inline list', 'poll-creator' ),
				],
			],
		];

		foreach ( $block_styles as $block_style ) {
			register_block_style( $block_style['block'], $block_style['style'] );
		}
	}

	/**
	 * Localize script.
	 */
	public function localize_script() {
		wp_localize_script(
			'wp-api-fetch',
			'pollify',
			array(
				'nonce' => wp_create_nonce( 'pollify-vote' ),
			)
		);
	}
}
