<?php
/**
 * Miusages rest route endpoint.
 *
 * @package AwesomeMotive\Miusage
 */

declare(strict_types=1);

namespace wpRigel\Pollify\REST;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use wpRigel\Pollify\Polls;
use wpRigel\Pollify\Model\Poll;

/**
 * MiusageController class.
 *
 * @package wpRigel\Pollify\API
 */
class PollsController extends WP_REST_Controller {

	/**
	 * Namespace for the endpoint.
	 *
	 * @var string
	 */
	protected $namespace = 'pollify/v1';

	/**
	 * Base URL for endpoint.
	 *
	 * @var string
	 */
	protected $action = 'polls';

	/**
	 * Register Routes for custom request.
	 *
	 * Get challenge: '/wp-json/pollify/v1/polls'.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->action,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'args'                => $this->get_collection_params(),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->action . '/(?P<id>[\d]+)/',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the object.', 'poll-creator' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],

			]
		);
	}

	/**
	 * Get data for challenge.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		// Get rest parameters.
		$params = $request->get_params();

		$data  = [];
		$polls = Polls::get_instance()->all( $params );

		foreach ( $polls as $poll ) {
			$item   = $this->prepare_item_for_response( $poll, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Create a single item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$args = $request->get_params();

		$poll = Polls::get_instance()->save( $args );

		if ( is_wp_error( $poll ) ) {
			return $poll;
		}

		return rest_ensure_response( $this->prepare_item_for_response( $poll->get_data(), $request ) );
	}

	/**
	 * Get a single item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		// Get rest parameters.
		$params = $request->get_params();

		$poll = Polls::get_instance()->get( $params['id'] );

		if ( is_wp_error( $poll ) ) {
			return $poll;
		}

		return rest_ensure_response( $this->prepare_item_for_response( $poll->get_data(), $request ) );
	}

	/**
	 * Update a single item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		// Get rest parameters.
		$params = $request->get_params();

		$poll = Polls::get_instance()->save( $params );

		if ( is_wp_error( $poll ) ) {
			return $poll;
		}

		return rest_ensure_response( $this->prepare_item_for_response( $poll, $request ) );
	}

	/**
	 * Delete a single item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Get rest parameters.
		$params = $request->get_params();

		$poll = Polls::get_instance()->delete( $params['id'] );

		if ( is_wp_error( $poll ) ) {
			return $poll;
		}

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Poll deleted successfully', 'poll-creator' ),
			]
		);
	}

	/**
	 * Prepare data for response
	 *
	 * @param array           $data Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function prepare_item_for_response( $data, $request ) {
		if ( empty( $data['id'] ) ) {
			return new WP_Error( 'rest_invalid_id', __( 'Invalid resource id.', 'poll-creator' ), [ 'status' => 404 ] );
		}

		$response = rest_ensure_response( $data, $request );
		$response->add_links( $this->prepare_links( $data ) );

		return apply_filters( 'pollify_rest_prepare_poll_object', $response, $data, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param Poll $data Object data.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $data ) {
		$links = [
			'self'       => [
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->action, $data['id'] ) ),
			],
			'collection' => [
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->action ) ),
			],
		];

		return $links;
	}

	/**
	 * Item schema
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Poll',
			'type'       => 'array',
			'properties' => [
				'id'           => [
					'description' => __( 'Unique identifier for the object.', 'poll-creator' ),
					'type'        => 'integer',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'title'        => [
					'description' => __( 'Poll title', 'poll-creator' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'description'  => [
					'required'    => false,
					'description' => __( 'Poll description', 'poll-creator' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
				'type'         => [
					'required'    => true,
					'description' => __( 'The poll type. It can be normal poll, quize or NPS', 'poll-creator' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'default'     => 'poll',
				],
				'status'       => [
					'required'    => true,
					'description' => __( 'Poll status', 'poll-creator' ),
					'type'        => 'string',
					'enum'        => [ 'draft', 'publish', 'trash', 'delete' ],
					'context'     => [ 'view', 'edit' ],
					'default'     => 'publish',
				],
				'reference'    => [
					'required'    => false,
					'description' => __( 'From where the poll was created', 'poll-creator' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
				'options'      => [
					'required'    => false,
					'description' => __( 'Poll options', 'poll-creator' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit' ],
				],
				'created_date' => [
					'description' => __( "The date the withdraw request has beed created in the site's timezone.", 'poll-creator' ),
					'type'        => 'date-time',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'updated_date' => [
					'description' => __( "The date the withdraw request has beed updated in the site's timezone.", 'poll-creator' ),
					'type'        => 'date-time',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}
}
