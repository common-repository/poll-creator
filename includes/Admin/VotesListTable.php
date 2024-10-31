<?php
/**
 * Menu class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Admin;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * VotesListTable class.
 *
 * @package wpRigel\Pollify
 *
 * @since 1.0.0
 */
class VotesListTable extends \WP_List_Table {

	/**
	 * Poll object.
	 *
	 * @var object
	 */
	private $poll;

	/**
	 * Per page no.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Table data.
	 *
	 * @var array
	 */
	private $table_data;

	/**
	 * Constructor.
	 *
	 * @param object $poll Poll object.
	 */
	public function __construct( $poll = null ) {
		if ( ! empty( $poll ) && is_object( $poll ) ) {
			$this->poll = $poll;
		} else {
			$poll_id    = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );
			$this->poll = \wpRigel\Pollify\Polls::get_instance()->get( $poll_id );
		}

		parent::__construct(
			[
				'singular' => __( 'Vote', 'poll-creator' ),
				'plural'   => __( 'Votess', 'poll-creator' ),
				'ajax'     => false,
			]
		);
	}

	/**
	 * Prepare the columns for rendering on items.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		$columns = array(
			'name'       => __( 'Name', 'poll-creator' ),
			'location'   => __( 'Location', 'poll-creator' ),
			'ip_address' => __( 'IP Address', 'poll-creator' ),
			'vote'       => __( 'Vote answer', 'poll-creator' ),
			'created_at' => __( 'Vote date', 'poll-creator' ),
		);

		return $columns;
	}

	/**
	 * Render the column cb.
	 *
	 * @param array  $item The current item.
	 * @param string $column_name The current column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'name':
			case 'location':
				return $item['user_location'];
			case 'ip_address':
				return $item['user_ip'];
			case 'vote':
				return $item['option'];
			case 'created_at':
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'created_at' => [ 'created_at', false ],
		];

		return $sortable_columns;
	}

	/**
	 * Render the column name.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_name( $item ) {
		$user_id = $item['user_id'] ?? 0;

		if ( $user_id ) {
			$user = get_user_by( 'ID', $user_id );
		}

		return ! empty( $user ) ? esc_html( $user->display_name ) : esc_html__( 'Guest', 'poll-creator' );
	}

	/**
	 * Render the column location.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_location( $item ) {
		if ( ! empty( $item['user_location'] ) ) {
			return sprintf(
				'<span class="flag-icon fi fi-%s fib"></span> %s',
				esc_html( strtolower( $item['user_location'] ) ),
				esc_html( pollify_get_country_name( $item['user_location'] ) )
			);
		} else {
			return __( 'Unknown', 'poll-creator' );
		}
	}

	/**
	 * Render the column created_at.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_created_at( $item ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) );
	}

	/**
	 * Render the table nav.
	 *
	 * @param string $which The position of the nav.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$selected_location = pollify_filter_input( INPUT_POST, 'location', POLLIFY_FILTER_SANITIZE_STRING );
			$selected_option   = pollify_filter_input( INPUT_POST, 'poll_option', POLLIFY_FILTER_SANITIZE_STRING );

			$locations = \wpRigel\Pollify\Votes::get_instance()->get_votes_location( $this->poll->get_client_id() );
			?>
			<div class="alignleft actions bulkactions">
				<select name="location" id="vote-location" >
					<option value="" <?php selected( '', $selected_location, true ); ?>><?php esc_html_e( 'All countries', 'poll-creator' ); ?></option>

					<?php foreach ( $locations as $location ) : ?>
						<?php if ( ! empty( $location['location'] ) ) : ?>
							<option value="<?php echo esc_attr( $location['location'] ); ?>" <?php selected( $location['location'], $selected_location, true ); ?>>
								<?php echo esc_html( pollify_get_country_name( $location['location'] ) ); ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>

				<select name="poll_option" id="vote-option" >
					<option value="" <?php selected( '', $selected_option, true ); ?>><?php esc_html_e( 'All answers', 'poll-creator' ); ?></option>

					<?php foreach ( $this->poll->get_options() as $option ) : ?>
						<option value="<?php echo esc_attr( $option['option_id'] ); ?>" <?php selected( $option['option_id'], $selected_option, true ); ?>><?php echo wp_kses_post( $option['option'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
				submit_button( __( 'Filter', 'poll-creator' ), '', 'filter_action', false, [ 'id' => 'pollify-filter-action-button' ] );
				?>
			</div>
			<?php
		}
	}

	/**
	 * Prepare the items for rendering on the table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$search   = pollify_filter_input( INPUT_POST, 's', POLLIFY_FILTER_SANITIZE_STRING );
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		// Set up the columns.
		$this->_column_headers = [ $columns, [], $sortable ];

		// Set per page depending on screen option.
		$this->per_page = $this->get_items_per_page( 'polls_per_page', 20 );

		// Set some default args for getting the table data.
		$args = [
			'client_id' => $this->poll->get_client_id(),
			'per_page'  => $this->per_page,
			'page'      => $this->get_pagenum(),
		];

		// Set type from extra nav filter.
		$location = pollify_filter_input( INPUT_POST, 'location', POLLIFY_FILTER_SANITIZE_STRING );
		$option   = pollify_filter_input( INPUT_POST, 'poll_option', POLLIFY_FILTER_SANITIZE_STRING );

		if ( ! empty( $location ) ) {
			$args['location'] = $location;
		}

		if ( ! empty( $option ) ) {
			$args['option'] = $option;
		}

		// Set search term if someonce search.
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		// Set order and order by depending on filter input value.
		$order   = pollify_filter_input( INPUT_GET, 'order', POLLIFY_FILTER_SANITIZE_STRING ) ?: 'DESC';
		$orderby = pollify_filter_input( INPUT_GET, 'orderby', POLLIFY_FILTER_SANITIZE_STRING ) ?: 'id';

		if ( isset( $orderby, $order ) && in_array( $orderby, array_keys( $sortable ), true ) && in_array( strtoupper( $order ), [ 'ASC', 'DESC' ], true ) ) {
			$args['orderby'] = $orderby;
			$args['order']   = $order;
		}

		// Get the table data depending on args.
		$this->table_data = $this->get_table_data( $args );

		// Get total counts depending on args.
		$args['count'] = true;
		$total_items   = intval( $this->get_table_data( $args ) );

		// Set the pagination.
		$this->set_pagination_args(
			[
				'total_items' => $total_items, // total number of items.
				'per_page'    => $this->per_page, // items to show on a page.
				'total_pages' => ceil( $total_items / $this->per_page ), // use ceil to round up.
			]
		);

		// Set the final items for dispalying.
		$this->items = $this->table_data;
	}

	/**
	 * Get data from tables.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|int
	 */
	private function get_table_data( $args ) {
		return $this->poll->get_votes( $args );
	}
}