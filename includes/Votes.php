<?php
/**
 * Vote class.
 *
 * Handle all vote CRUD operation in one class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use WP_Error;
use wpRigel\Pollify\Model\Voter;
use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Votes.
 *
 * Handle all vote CRUD operation in one class.
 */
class Votes {

	use Singleton;

	/**
	 * Poll table name.
	 *
	 * @var string
	 */
	private string $table_name = 'pollify_vote';

	/**
	 * Set a vote for a poll.
	 *
	 * @param array $args Arguments for setting a vote.
	 */
	public function vote( array $args = [] ) {
		global $wpdb;

		$defaults = [
			'client_id'  => 0,
			'option_ids' => [],
			'user_id'    => 0,
			'user_ip'    => '',
			'user_agent' => '',
			'created_at' => current_time( 'mysql' ),
		];

		$args = wp_parse_args( $args, $defaults );

		// Check if poll_id and option_id empty or not.
		if ( empty( $args['client_id'] ) || empty( $args['option_ids'] ) ) {
			return new WP_Error( 'empty_poll_id_or_option_id', __( 'Poll ID or Option ID is empty.', 'poll-creator' ) );
		}

		$poll = Polls::get_instance()->get( $args['client_id'] );

		// Checking if poll exist or not.
		if ( ! $poll || is_wp_error( $poll ) ) {
			return new WP_Error( 'poll_not_exist', __( 'Invalid poll data.', 'poll-creator' ) );
		}

		// Checking if valid poll option or not.
		if ( ! is_array( $args['option_ids'] ) || ! $poll->is_valid_poll_option( (array) $args['option_ids'] ) ) {
			return new WP_Error( 'invalid_poll_option', __( 'Invalid poll option.', 'poll-creator' ) );
		}

		// Get user data from Voter class.
		$voter = new Voter();

		// Set all user parameters.
		$args['user_id']       = $voter->get_user_id();
		$args['user_ip']       = $voter->get_user_ip();
		$args['user_agent']    = $voter->get_user_agent();
		$args['user_location'] = $voter->get_user_country();

		// Loop through all option ids and set vote for each option.
		foreach ( $args['option_ids'] ?? [] as $option_id ) {
			// Insert vote data into database.
			$inserted = $wpdb->insert(
				$wpdb->prefix . $this->table_name,
				[
					'client_id'     => $args['client_id'],
					'option_id'     => $option_id,
					'user_id'       => $args['user_id'],
					'user_ip'       => $args['user_ip'],
					'user_location' => $args['user_location'],
					'user_agent'    => $args['user_agent'],
					'created_at'    => $args['created_at'],
				],
				[
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
				]
			);

			if ( ! $inserted ) {
				return new WP_Error( 'vote_not_inserted', __( 'Sorry vote not accepted.', 'poll-creator' ) );
			}
		}

		$vote_data       = $args;
		$vote_data['id'] = $wpdb->insert_id;

		// Reset cache group for rendering the cache again.
		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
			wp_cache_flush_group( 'pollify_vote_cache' );
		}

		// Return success message.
		return $vote_data;
	}

	/**
	 * Get votes for specific poll.
	 * By default it will return latest 15 votes. Rest of the other things will be loaded via pagination.
	 *
	 * @param array $args Argument for getting votes.
	 *
	 * @return array|int
	 */
	public function get_votes( $args = [] ) {
		global $wpdb;

		$default = [
			'client_id' => '',
			'per_page'  => 15,
			'page'      => 1,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		];

		$args = wp_parse_args( $args, $default );

		if ( empty( $args['client_id'] ) ) {
			return [];
		}

		// Create some where condition regarding status, type, search etc.
		$where = $wpdb->prepare( 'WHERE 1=%d', 1 );

		// Check if client_id is empty or not.
		if ( ! empty( $args['client_id'] ) ) {
			$where .= $wpdb->prepare( ' AND v.client_id = %s', sanitize_text_field( $args['client_id'] ) );
		}

		// Check if location is available or not.
		if ( ! empty( $args['user_id'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_id = %d', sanitize_text_field( $args['user_id'] ) );
		}

		// Check if location is available or not.
		if ( ! empty( $args['location'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_location = %s', sanitize_text_field( $args['location'] ) );
		}

		// Check if ip is available or not.
		if ( ! empty( $args['ip'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip = %s', sanitize_text_field( $args['ip'] ) );
		}

		// Check if option is availble for filter.
		if ( ! empty( $args['option'] ) ) {
			$where .= $wpdb->prepare( ' AND o.option_id = %s', sanitize_text_field( $args['option'] ) );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip LIKE %s', '%' . sanitize_text_field( $args['search'] ) . '%' );
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Set orderby clause using prepare.
		$order_by = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );

		if ( ! empty( $args['count'] ) && $args['count'] ) {
			// Implement cache here for count param.
			$cache_count_key = 'pollify_votes_count_' . md5( maybe_serialize( $args ) );
			$votes           = wp_cache_get( $cache_count_key, 'pollify_vote_cache' );

			if ( false === $votes ) {
				// Get vote data.
				$votes = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT COUNT(v.id), o.option, o.option_id FROM %i v LEFT JOIN %i o ON v.option_id = o.option_id {$where}",
						$wpdb->prefix . $this->table_name,
						$wpdb->prefix . 'pollify_poll_options'
					)
				);

				wp_cache_set( $cache_count_key, $votes, 'pollify_vote_cache', 15 * MINUTE_IN_SECONDS );
			}

			return intval( $votes ) ?? 0;
		}

		// Implement cache for getting rows.
		$cache_key = 'pollify_votes_' . md5( maybe_serialize( $args ) );
		$votes     = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false === $votes ) {
			// Prepare the sql query.
			$votes = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT v.*, o.option, o.option_id FROM {$wpdb->prefix}{$this->table_name} v LEFT JOIN {$wpdb->prefix}pollify_poll_options o ON v.option_id = o.option_id {$where} ORDER BY {$order_by} LIMIT %d OFFSET %d",
					$args['per_page'],
					$offset
				),
				ARRAY_A
			);

			wp_cache_set( $cache_key, $votes, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
		}

		return $votes ?? [];
	}

	/**
	 * Get results for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return array
	 */
	public function get_results( string $client_id ): array {
		global $wpdb;

		// Get poll options.
		$poll    = Polls::get_instance()->get( $client_id );
		$options = ! is_wp_error( $poll ) ? $poll->get_options() : [];

		// Implement caching.
		$cache_key = 'pollify_results_' . $client_id;
		$results   = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false === $results ) {
			// Get vote data.
			$votes = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT option_id, COUNT(*) as votes FROM %i WHERE client_id = %s GROUP BY option_id',
					$wpdb->prefix . $this->table_name,
					$client_id
				),
				ARRAY_A
			);

			$total_votes = array_sum( wp_list_pluck( $votes, 'votes' ) );

			if ( ! empty( $options ) ) {
				// Loop through all options and set total votes for each option.
				foreach ( $options as $key => $option ) {
					$options[ $key ]['votes']      = 0;
					$options[ $key ]['percentage'] = 0;

					foreach ( $votes as $vote ) {
						if ( $option['option_id'] === $vote['option_id'] ) {
							$options[ $key ]['votes'] = (int) $vote['votes'];

							// Calculate percentage.
							$options[ $key ]['percentage'] = (int) $vote['votes'] > 0 ? number_format_i18n( ( (int) $vote['votes'] / (int) $total_votes ) * 100, 2 ) : 0;

						}
					}
				}
			}

			$results = [
				'total_votes'  => intval( $total_votes ),
				'voter_counts' => count( $votes ),
				'options'      => $options ?? [],
			];

			wp_cache_set( $cache_key, $results, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
		}

		return $results ?? [];
	}

	/**
	 * Get votes group by IP.
	 *
	 * @param array $args Arguments for getting votes.
	 *
	 * @return array|int
	 */
	public function get_ip_votes( array $args ) {
		global $wpdb;

		$default = [
			'client_id' => '',
			'per_page'  => 15,
			'page'      => 1,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		];

		$args = wp_parse_args( $args, $default );

		if ( empty( $args['client_id'] ) ) {
			return [];
		}

		// Create some where condition regarding status, type, search etc.
		$where = $wpdb->prepare( 'WHERE 1=%d', 1 );

		// Check if client_id is empty or not.
		if ( ! empty( $args['client_id'] ) ) {
			$where .= $wpdb->prepare( ' AND v.client_id = %s', sanitize_text_field( $args['client_id'] ) );
		}

		// Check if client_id is empty or not.
		if ( ! empty( $args['location'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_location = %s', sanitize_text_field( $args['location'] ) );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip LIKE %s', '%' . sanitize_text_field( $args['search'] ) . '%' );
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// If count is exist then return the count.
		if ( ! empty( $args['count'] ) && $args['count'] ) {
			// Implement cacjiing for count param.
			$cache_count_key = 'pollify_ip_votes_count_' . md5( maybe_serialize( $args ) );
			$votes           = wp_cache_get( $cache_count_key, 'pollify_vote_cache' );

			if ( false === $votes ) {
				// Get vote data.
				$votes = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT COUNT(DISTINCT user_ip) FROM %i v {$where}",
						$wpdb->prefix . $this->table_name
					)
				);

				wp_cache_set( $cache_count_key, $votes, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
			}

			return intval( $votes ) ?? 0;
		}

		// Implement cache for getting rows.
		$cache_key = 'pollify_ip_votes_' . md5( maybe_serialize( $args ) );
		$votes     = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		// Set order by clause using prepare.
		$order_by = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );

		if ( false === $votes ) {
			// Get vote data.
			$votes = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT user_ip as ip, user_location as location, COUNT(*) as votes FROM %i v {$where} GROUP BY user_ip ORDER BY {$order_by} LIMIT %d OFFSET %d",
					$wpdb->prefix . $this->table_name,
					$args['per_page'],
					$offset
				),
				ARRAY_A
			);

			wp_cache_set( $cache_key, $votes, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
		}

		return $votes ?? [];
	}

	/**
	 * Get all vote locations for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return array
	 */
	public function get_votes_location( string $client_id ): array {
		global $wpdb;

		// Implement cache for getting rows.
		$cache_key = 'pollify_votes_location_' . $client_id;
		$locations = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false !== $locations ) {
			return $locations;
		}

		// Get vote data.
		$locations = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT user_location as location FROM %i WHERE client_id = %s',
				$wpdb->prefix . $this->table_name,
				$client_id
			),
			ARRAY_A
		);

		wp_cache_set( $cache_key, $locations, 'pollify_vote_cache', 15 * MINUTE_IN_SECONDS );

		return $locations ?? [];
	}

	/**
	 * Reset results for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return bool
	 */
	public function reset_results( string $client_id ): bool {
		global $wpdb;

		// Delete all votes for a poll.
		$deleted = $wpdb->delete(
			$wpdb->prefix . $this->table_name,
			[
				'client_id' => $client_id,
			],
			[
				'%s',
			]
		);

		return (bool) $deleted;
	}
}
