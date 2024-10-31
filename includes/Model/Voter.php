<?php
/**
 * Voter model class.
 *
 * @package wpRigel\Pollify
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Model;

use wpRigel\Pollify\Votes;

/**
 * Class Voter.
 *
 * Handle a single Voter object with all its data.
 */
class Voter {

	/**
	 * Voter data.
	 *
	 * @var array
	 */
	private array $data = [
		'user_id'    => 0,
		'user_ip'    => '',
		'user_agent' => '',
	];

	/**
	 * Voter constructor.
	 */
	public function __construct() {
		// Check if user is logged in or not.
		$this->data['user_id'] = $this->get_user_id();

		// Get user IP.
		$this->data['user_ip'] = $this->get_user_ip();

		// Get user agent.
		$this->data['user_agent'] = $this->get_user_agent();
	}

	/**
	 * Get user ID.
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return is_user_logged_in() ? get_current_user_id() : 0;
	}

	/**
	 * Function to check if an IP address is from localhost or a local network.
	 *
	 * @param string $ip The IP address to check.
	 *
	 * @return bool True if the IP is from localhost or a local network, false otherwise.
	 */
	public function is_local_ip( $ip ) {
		// Localhost.
		if ( '127.0.0.1' === $ip || '::1' === $ip ) {
			return true;
		}

		// Local network IP ranges.
		$local_ip_ranges = [
			'10.0.0.0|10.255.255.255',        // Class A private network.
			'172.16.0.0|172.31.255.255',      // Class B private network.
			'192.168.0.0|192.168.255.255',    // Class C private network.
			'169.254.0.0|169.254.255.255',    // Link-local address (APIPA).
		];

		$long_ip = ip2long( $ip );

		if ( false !== $long_ip ) {
			foreach ( $local_ip_ranges as $range ) {
				list( $start, $end ) = explode( '|', $range );

				if ( $long_ip >= ip2long( $start ) && $long_ip <= ip2long( $end ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get user IP.
	 *
	 * @return string
	 */
	public function get_user_ip(): string {
		// Get user IP address.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// Check IP from internet.
			$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Check IP is passed from proxy.
			$ip = sanitize_text_field( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0] );
		} else {
			// Get IP address.
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		// Check if the IP is localhost or local network then don't need to pass filter_var.
		if ( $this->is_local_ip( $ip ) ) {
			return $ip;
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return $ip;
		}

		return '';
	}

	/**
	 * Get user country depending on IP.
	 *
	 * @return string
	 */
	public function get_user_country(): string {
		$ip = $this->get_user_ip();

		// If someone is using localhost or local network then don't need to pass the IP.
		// Geoplugin will get automatically IP location from the server.
		if ( $this->is_local_ip( $ip ) ) {
			$ip = '';
		}

		$url  = 'http://www.geoplugin.net/json.gp?ip=' . $ip;
		$data = wp_remote_get( $url );

		if ( ! is_wp_error( $data ) ) {
			// Get the body of the response.
			$body     = wp_remote_retrieve_body( $data );
			$response = json_decode( $body, true );
		}

		return $response['geoplugin_countryCode'] ?? '';
	}

	/**
	 * Get user agent.
	 *
	 * @return string
	 */
	public function get_user_agent(): string {
		return sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ?? '';
	}

	/**
	 * Get user votes.
	 *
	 * @param string $client_id Poll client id.
	 *
	 * @return array
	 */
	public function get_votes( string $client_id ): array {
		return Votes::get_instance()->get_votes( [ 'client_id' => $client_id ] );
	}

	/**
	 * Is user already voted or not.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return boolean
	 */
	public function is_already_voted( string $client_id ): bool {
		$votes = Votes::get_instance()->get_votes(
			[
				'per_page'  => 1,
				'client_id' => $client_id,
				'user_id'   => $this->get_user_id(),
			]
		);

		if ( ! empty( $votes ) ) {
			return true;
		}

		$votes = Votes::get_instance()->get_ip_votes(
			[
				'per_page'  => 1,
				'client_id' => $client_id,
				'user_ip'   => $this->get_user_ip(),
			]
		);

		if ( ! empty( $votes ) ) {
			return true;
		}

		return false;
	}
}
