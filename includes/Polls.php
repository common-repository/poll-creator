<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use WP_Error;
use wpRigel\Pollify\Model\Poll;
use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Polls.
 *
 * Handle all poll CRUD operation in one class.
 */
class Polls {
	use Singleton;

	/**
	 * Poll table name.
	 *
	 * @var string
	 */
	private string $poll_table_name = 'pollify_poll';

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private string $poll_option_table_name = 'pollify_poll_options';

	/**
	 * Get all polls.
	 *
	 * @param array $args Poll arguments.
	 *
	 * @return array|WP_Error
	 */
	public function all( $args = [] ) {
		global $wpdb;

		$default = [
			'per_page' => '10',
			'page'     => 1,
			'status'   => 'publish',
			'orderby'  => 'id',
			'order'    => 'DESC',
		];

		$table_name = $wpdb->prefix . $this->poll_table_name;

		// @TODO::Need to handle those args for querying data.
		$args = wp_parse_args( $args, $default );

		// Create some where condition regarding status, type, search etc.
		$where = $wpdb->prepare( 'WHERE 1=%d', 1 );

		// If status is set then add where condition for status.
		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where .= $wpdb->prepare( ' AND status = %s', $args['status'] );
		}

		// If type is set then add where condition for type.
		if ( ! empty( $args['type'] ) && 'all' !== $args['type'] ) {
			$where .= $wpdb->prepare( ' AND type = %s', $args['type'] );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND poll.title LIKE %s', '%' . $args['search'] . '%' );
		}

		// Set the pagination data.
		$per_page = intval( $args['per_page'] );
		$page     = intval( $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		// Set pagination in query.
		$limit = $wpdb->prepare( 'LIMIT %d, %d', $offset, $per_page );

		// Join with wp_pollify_vote table and get the total count of votes related to client id.
		$join = $wpdb->prepare( 'LEFT JOIN %i AS vote ON vote.client_id = poll.client_id', $wpdb->prefix . 'pollify_vote' );

		// If we pass count parament true in args then just count the polls and return the count.
		if ( ! empty( $args['count'] ) && $args['count'] ) {
			// Implement cache for poll data.
			$cache_key_count = 'polls_count_' . md5( maybe_serialize( $args ) );
			$count           = wp_cache_get( $cache_key_count, 'pollify_poll_cache' );

			if ( false === $count ) {
				$count = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT COUNT(`id`) FROM %i as poll {$where}",
						$table_name
					)
				);

				wp_cache_set( $cache_key_count, $count, 'pollify_poll_cache', 15 * MINUTE_IN_SECONDS );
			}

			return intval( $count );
		}

		// Implement orderby clause sanitization.
		$order_by = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );

		$cache_key = 'polls_' . md5( maybe_serialize( $args ) );

		$polls = wp_cache_get( $cache_key, 'pollify_poll_cache' );

		if ( false === $polls ) {
			// Get all polls from database.
			$polls = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT poll.*, COUNT(vote.id) as response FROM %i AS poll {$join} {$where} GROUP BY poll.id ORDER BY {$order_by} {$limit}",
					$table_name
				),
				ARRAY_A
			);

			// Filter each $poll and return only settings as an array by json decoding.
			$polls = array_map(
				function ( $poll ) {
					$poll['settings'] = json_decode( $poll['settings'], true );
					return $poll;
				},
				$polls
			);

			wp_cache_set( $cache_key, $polls, 'pollify_poll_cache', 15 * MINUTE_IN_SECONDS );
		}

		return $polls;
	}

	/**
	 * Create or update poll data depeneing on ID.
	 *
	 * @param array $args Poll arguments.
	 *
	 * @return array|WP_Error
	 */
	public function save( $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'id'          => 0,
				'client_id'   => '',
				'title'       => '',
				'description' => '',
				'type'        => 'poll',
				'status'      => 'publish',
				'reference'   => null,
				'options'     => [],
				'created_at'  => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
				'settings'    => null,
			]
		);

		// Checking title, type and status is empty or not. If empty return WP_Error.
		foreach ( $args as $key => $value ) {
			$skip_fields = [ 'id', 'title', 'description', 'client_id', 'reference', 'options', 'settings' ];

			if ( empty( $value ) && ! in_array( $key, $skip_fields, true ) ) {
				return new WP_Error(
					'empty-data',
					wp_sprintf(
						/* translators: %s: Field name */
						__( 'Error: %s cannot be left empty. Please fill the required information', 'poll-creator' ),
						$key
					),
					[ 'status' => 400 ]
				);
			}
		}

		// Handle the poll options.
		// - id.
		// - option_id: random_string.
		// - type: text|image.
		// - option: Text|Image object(id/url).

		$args['options'] = array_filter(
			$args['options'] ?? [],
			function ( $option ) {
				return ! empty( $option['option'] );
			}
		);

		foreach ( $args['options'] ?? [] as $option ) {
			// Checking if options is array of array or not. If yes then.
			if ( is_array( $option ) ) {
				if ( ( ! array_key_exists( 'option', $option ) || ! array_key_exists( 'type', $option ) ) ) {
					return new WP_Error( 'not-formatted-options', __( 'Options must contain type and option value', 'poll-creator' ), [ 'status' => 400 ] );
				}
			} elseif ( ( ! array_key_exists( 'option', $args['options'] ) || ! array_key_exists( 'type', $args['options'] ) ) ) {
					return new WP_Error( 'not-formatted-options', __( 'Options must contain type and option value', 'poll-creator' ), [ 'status' => 400 ] );
			}
		}

		// Now all set. If we have valid poll ID then update the poll data. Otherwise create a new poll.
		if ( ! preg_match( '/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $args['client_id'] ) ) {
			return new WP_Error( 'invalid-client-id', __( 'Client id is not valid', 'poll-creator' ), [ 'status' => 400 ] );
		}

		// Get poll data using client_id.
		$poll = $this->get( $args['client_id'] );

		// Check if client_id is avilable and valid uuid using regex or not.
		if ( ! is_wp_error( $poll ) ) {

			$updated = $wpdb->update(
				$wpdb->prefix . $this->poll_table_name,
				[
					'title'       => $args['title'],
					'description' => $args['description'],
					'type'        => $args['type'],
					'status'      => $args['status'],
					'reference'   => $args['reference'],
					'updated_at'  => current_time( 'mysql' ),
					'settings'    => $args['settings'],
				],
				[
					'client_id' => $args['client_id'],
					'id'        => $poll->get_id(),
				],
				[
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				],
				[ '%s' ]
			);

			if ( ! $updated ) {
				return new WP_Error( 'update-failed', __( 'Poll not updated successfully', 'poll-creator' ), [ 'status' => 422 ] );
			}

			$option_saved = $this->save_options( intval( $poll->get_id() ), $args['options'] );

			// If option not saved then return WP_Error.
			if ( is_wp_error( $option_saved ) ) {
				return $option_saved;
			}
		} else {
			// If poll ID is empty then create a new poll.
			$inserted = $wpdb->insert(
				$wpdb->prefix . $this->poll_table_name,
				[
					'client_id'   => $args['client_id'],
					'title'       => $args['title'],
					'description' => $args['description'],
					'type'        => $args['type'],
					'status'      => $args['status'],
					'reference'   => $args['reference'],
					'created_at'  => current_time( 'mysql' ),
					'updated_at'  => current_time( 'mysql' ),
					'settings'    => $args['settings'],
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);

			if ( ! $inserted ) {
				return new WP_Error( 'insert-failed', __( 'Poll not created successfully', 'poll-creator' ), [ 'status' => 422 ] );
			}

			$args['id'] = $wpdb->insert_id;

			$option_saved = $this->save_options( intval( $args['id'] ), $args['options'] );

			// If option not saved then return WP_Error.
			if ( is_wp_error( $option_saved ) ) {
				return $option_saved;
			}
		}

		// Delete the cache after updating or inserting the exisitng or new poll.
		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
		}

		return $this->get( $args['id'] );
	}

	/**
	 * Get a Poll.
	 *
	 * @param int $client_id Poll client ID.
	 *
	 * @return Poll|WP_Error
	 */
	public function get( $client_id ) {
		global $wpdb;

		// Get poll data from cache if has any.
		$cache_key = 'poll_' . $client_id;

		$poll = wp_cache_get( $cache_key, 'pollify_poll_cache' );

		if ( false === $poll ) {
			$poll = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE client_id = %s',
					$wpdb->prefix . $this->poll_table_name,
					$client_id,
				),
				ARRAY_A
			);

			if ( ! $poll ) {
				return new WP_Error( 'not-found', __( 'Poll not found', 'poll-creator' ), [ 'status' => 404 ] );
			}

			$poll_options = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT `id`,`option_id`,`type`,`option` FROM %i WHERE poll_id = %d',
					$wpdb->prefix . 'pollify_poll_options',
					intval( $poll['id'] )
				),
				ARRAY_A
			);

			$poll['options'] = $poll_options;

			wp_cache_set( $cache_key, $poll, 'pollify_poll_cache', 15 * MINUTE_IN_SECONDS );
		}

		return new Poll( $poll );
	}

	/**
	 * Delete a Poll.
	 *
	 * @param int $client_id Poll client ID.
	 *
	 * @return bool|WP_Error
	 */
	public function delete( $client_id ) {
		// Delete poll and poll options from their respective tables.
		global $wpdb;

		// Get poll id from poll client ID.
		$poll = $this->get( $client_id );

		if ( is_wp_error( $poll ) ) {
			return $poll;
		}

		$poll_id = $poll->get_id();

		$deleted = $wpdb->delete(
			$wpdb->prefix . $this->poll_table_name,
			[ 'client_id' => $client_id ],
			[ '%s' ]
		);

		if ( ! $deleted ) {
			return new WP_Error( 'deletion-failed', __( 'Poll not deleted successfully', 'poll-creator' ), [ 'status' => 422 ] );
		}

		$deleted = $wpdb->delete(
			$wpdb->prefix . 'pollify_poll_options',
			[ 'poll_id' => $poll_id ],
			[ '%d' ]
		);

		if ( ! $deleted ) {
			return new WP_Error( 'deletion-failed', __( 'Poll not deleted successfully', 'poll-creator' ), [ 'status' => 422 ] );
		}

		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
		}

		return true;
	}

	/**
	 * Check if poll exist or not.
	 *
	 * @param int $client_id Poll client ID.
	 *
	 * @return bool
	 */
	public function exist( $client_id ): bool {
		$poll = $this->get( $client_id );

		return ! is_wp_error( $poll );
	}

	/**
	 * Check if poll with valid options.
	 *
	 * @param string $client_id Poll client ID.
	 * @param array  $option_ids Poll option IDs.
	 *
	 * @return bool
	 */
	public function is_valid_poll_option( string $client_id, array $option_ids ): bool {
		global $wpdb;

		$valid = true;

		// Want to check each option id is valid or not.
		foreach ( $option_ids as $option_id ) {
			$poll_option = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE option_id = %s AND client_id = %s',
					$wpdb->prefix . $this->poll_option_table_name,
					$option_id,
					$client_id
				),
				ARRAY_A
			);

			if ( empty( $poll_option ) ) {
				$valid = false;
				break;
			}
		}

		return $valid;
	}

	/**
	 * Save poll options.
	 *
	 * @param int   $poll_id Poll ID.
	 * @param array $options Poll options.
	 *
	 * @return array|WP_Error
	 */
	public function save_options( int $poll_id, array $options ) {
		global $wpdb;

		if ( empty( $poll_id ) ) {
			return new WP_Error( 'empty-poll-id', __( 'Poll ID cannot be empty', 'poll-creator' ), [ 'status' => 400 ] );
		}

		// Get all poll options using $poll_id.
		$poll_options = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `id`,`option_id`,`type`,`option` FROM %i WHERE poll_id = %d',
				$wpdb->prefix . 'pollify_poll_options',
				$poll_id
			),
			ARRAY_A
		);

		// Find those options which are not in $options array.
		$deleted_options = array_diff( array_column( $poll_options, 'option_id' ), array_column( $options, 'option_id' ) );

		// Delete those options if has any in a single query.
		if ( count( $deleted_options ) ) {
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				str_replace(
					'\\',
					'',
					$wpdb->prepare(
						'DELETE FROM %i WHERE option_id IN (%s)',
						$wpdb->prefix . 'pollify_poll_options',
						implode( "','", $deleted_options )
					)
				)
			);
		}

		// Filter those array from $options where id key is not set or id is 0 or null.
		$new_options = array_filter(
			$options,
			function ( $option ) use ( $poll_options ) {
				$option_ids = wp_list_pluck( $poll_options, 'option_id' );
				return ! in_array( $option['option_id'], $option_ids, true );
			}
		);

		// If we have new options then insert those option using foreach loop.
		if ( count( $new_options ) ) {
			foreach ( $new_options as $option ) {
				$wpdb->insert(
					$wpdb->prefix . 'pollify_poll_options',
					[
						'poll_id'   => intval( $poll_id ),
						'option_id' => $option['option_id'],
						'type'      => $option['type'],
						'option'    => $option['option'],
					],
					[ '%d', '%s', '%s', '%s' ]
				);
			}
		}

		// Options which we need to updated dpending on changes type and option
		// for $options array compare with $poll_options array where id is same.
		// If type and option is different then update the option.
		$update_options = array_filter(
			$options,
			function ( $option ) use ( $poll_options ) {
				foreach ( $poll_options as $poll_option ) {
					if ( ! empty( $option['option_id'] )
						&& ( $poll_option['option_id'] === $option['option_id'] )
					) {
						return $poll_option['type'] !== $option['type'] || $poll_option['option'] !== $option['option'];
					}
				}
			}
		);

		// If we have updated options then update those options using foreach loop.
		if ( count( $update_options ) ) {
			foreach ( $update_options as $option ) {
				$wpdb->update(
					$wpdb->prefix . 'pollify_poll_options',
					[
						'type'   => $option['type'],
						'option' => $option['option'],
					],
					[ 'option_id' => $option['option_id'] ],
					[ '%s', '%s' ],
					[ '%s' ]
				);
			}
		}

		return $options;
	}
}
