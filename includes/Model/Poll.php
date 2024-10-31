<?php
/**
 * Poll model class.
 *
 * @package wpRigel\Pollify
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Model;

use WP_Error;
use wpRigel\Pollify\Votes;

/**
 * Class Poll.
 *
 * Handle a single poll object with all its data.
 */
class Poll {

	/**
	 * Poll data.
	 *
	 * @var array
	 */
	private array $data = [
		'id'          => 0,
		'client_id'   => '',
		'title'       => '',
		'description' => '',
		'type'        => '',
		'status'      => '',
		'reference'   => '',
		'options'     => [],
		'created_at'  => '',
		'updated_at'  => '',
		'settings'    => [],
	];

	/**
	 * Poll constructor.
	 *
	 * @param array $args Poll arguments.
	 */
	public function __construct( array $args = [] ) {
		// Need to set $data array with $args array in such way like only $data array keys will be set
		// which are exists in $args array.
		$this->data = array_merge(
			$this->data,
			array_intersect_key(
				$args,
				$this->data
			)
		);

		if ( ! empty( $this->data['settings'] ) ) {
			$this->data['settings'] = json_decode( $this->data['settings'] ?? '', true, 512 );
		}
	}

	/**
	 * Get poll ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return intval( $this->data['id'] );
	}

	/**
	 * Get poll client ID.
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		return $this->data['client_id'];
	}

	/**
	 * Get poll title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->data['title'];
	}

	/**
	 * Get poll description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->data['description'];
	}

	/**
	 * Get poll type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->data['type'];
	}

	/**
	 * Get poll status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->data['status'];
	}

	/**
	 * Get poll reference.
	 *
	 * @return string
	 */
	public function get_reference(): string {
		return $this->data['reference'];
	}

	/**
	 * Get poll options.
	 *
	 * @return array
	 */
	public function get_options(): array {
		return $this->data['options'];
	}

	/**
	 * Get poll created at.
	 *
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->data['created_at'];
	}

	/**
	 * Get poll updated at.
	 *
	 * @return array
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Get poll settings.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		return maybe_unserialize( $this->data['settings'] );
	}

	/**
	 * Check if poll is closed or not.
	 *
	 * @return bool
	 */
	public function is_poll_closed(): bool {
		$settings = $this->get_settings();

		if ( 'draft' === $settings['status'] ) {
			return true;
		}

		if ( 'schedule' === $settings['status'] && ! empty( $settings['endDate'] ) ) {
			$end_date = strtotime( $settings['endDate'] );

			if ( $end_date < time() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check all options is valid which is passed by arguments.
	 *
	 * @param array $options Options.
	 *
	 * @return bool
	 */
	public function is_valid_poll_option( array $options = [] ): bool {
		$valid = true;

		// Want to check each option id is valid or not.
		foreach ( $options as $option_id ) {
			$poll_option = array_filter(
				$this->get_options(),
				function ( $option ) use ( $option_id ) {
					return $option['option_id'] === $option_id;
				}
			);

			if ( empty( $poll_option ) ) {
				$valid = false;
				break;
			}
		}

		return $valid;
	}

	/**
	 * Do vote.
	 *
	 * @param array $options Vote options.
	 *
	 * @return array|WP_Error
	 */
	public function vote( array $options = [] ) {
		// Get poll settings.
		$settings = $this->get_settings();

		// Check if options is empty or not.
		if ( empty( $options ) ) {
			return new WP_Error( 'empty-options', __( 'Options are empty.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		if ( $this->is_poll_closed() ) {
			return new WP_Error( 'poll-closed', wp_kses_post( $settings['closePollmessage'] ?? __( 'This poll is closed', 'poll-creator' ) ), [ 'status' => 400 ] );
		}

		// Get the voter details from Voter model class.
		$voter = new Voter();

		// If Poll settings is enabled for per computer vote then check if user already voted or not.
		if (
			! empty( $settings['allowedPerComputerResponse'] )
			&& $voter->is_already_voted( $this->get_client_id() )
		) {
			return new WP_Error( 'already-voted', __( 'You have already voted.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		// Save the vote data.
		$vote = Votes::get_instance()->vote(
			[
				'client_id'  => $this->get_client_id(),
				'option_ids' => $options,
			]
		);

		if ( is_wp_error( $vote ) ) {
			return $vote;
		}

		// Reset all user related params before sending via REST.
		unset( $vote['user_id'], $vote['user_ip'], $vote['user_location'], $vote['user_agent'] );

		// Set vote return data.
		$data = [
			'success'  => true,
			'data'     => $vote,
			'settings' => $settings,
		];

		// Check if the settings is view-result then set the result data.
		if (
			! empty( $settings['confirmationMessageType'] )
			&& 'view-result' === $settings['confirmationMessageType']
		) {
			$results = $this->get_results();

			// Pass the result in result template file and return the resust with template.
			ob_start();
			pollify_load_template(
				'results/horizointal-bar-chart.php',
				false,
				[
					'data' => $results,
				]
			);
			$data['resultTemplate'] = ob_get_clean();
			$data['result']         = $results;
		}

		return $data;
	}

	/**
	 * Get results.
	 *
	 * @return array
	 */
	public function get_results(): array {
		// Get the vote result.
		$result = Votes::get_instance()->get_results( $this->get_client_id() );

		return $result;
	}

	/**
	 * Get the poll vote data.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|int
	 */
	public function get_votes( $args = [] ) {
		$default = [
			'client_id' => $this->get_client_id(),
		];

		$args = wp_parse_args( $args, $default );

		// Get the vote result.
		$result = Votes::get_instance()->get_votes( $args );

		return $result;
	}

	/**
	 * Get the votes by IP.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|int
	 */
	public function get_ip_votes( $args = [] ) {
		$default = [
			'client_id' => $this->get_client_id(),
		];

		$args = wp_parse_args( $args, $default );

		// Get the vote result.
		$result = Votes::get_instance()->get_ip_votes( $args );

		return $result;
	}
}
