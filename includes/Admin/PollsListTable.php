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
 * PollsListTable class.
 *
 * @package wpRigel\Pollify
 *
 * @since 1.0.0
 */
class PollsListTable extends \WP_List_Table {

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
	 * Prepare the columns for rendering on items.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		$columns = array(
			'cb'         => '<input type = "checkbox" />',
			'title'      => __( 'Title', 'poll-creator' ),
			'type'       => __( 'Type', 'poll-creator' ),
			'reference'  => __( 'Source', 'poll-creator' ),
			'status'     => __( 'Status', 'poll-creator' ),
			'response'   => __( 'Total Response', 'poll-creator' ),
			'created_at' => __( 'Created at', 'poll-creator' ),
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
			case 'client_id':
			case 'title':
			case 'type':
			case 'status':
			case 'reference':
			case 'response':
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
			'title'      => [ 'title', false ],
			'created_at' => [ 'created_at', false ],
		];

		return $sortable_columns;
	}

	/**
	 * Render the column cb.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="poll_id[]" value="%d" />',
			$item['id']
		);
	}

	/**
	 * Add row actions with column title.
	 *
	 * @param array $item The current item.
	 *
	 * @return array
	 */
	public function column_title( $item ) {
		$page         = pollify_filter_input( INPUT_GET, 'page', POLLIFY_FILTER_SANITIZE_STRING );
		$confirm_text = __( 'Are you sure you want to reset the results? If you do reset, the results are not achievable again.', 'poll-creator' );

		$nocne = wp_create_nonce( 'pollify_reset_results' );

		$actions = array(
			'view'  => sprintf( '<a href="?page=%s&action=%s&poll_id=%s">' . __( 'View results', 'poll-creator' ) . '</a>', $page, 'view_results', $item['client_id'] ),
			'trash' => sprintf( '<a class="submitdelete" onclick="return confirm(\'%s\')" href="?page=%s&action=%s&poll_id=%s&_nonce=%s">' . __( 'Reset Results', 'poll-creator' ) . '</a>', $confirm_text, $page, 'reset_results', $item['client_id'], $nocne ),
		);

		// Wrap the title with view result link.
		$title = sprintf( '<a href="?page=%s&action=%s&poll_id=%s">%s</a>', $page, 'view_results', $item['client_id'], $item['title'] );

		return sprintf( '<strong>%1$s</strong> %2$s', $title, $this->row_actions( $actions ) );
	}

	/**
	 * Render the column type using icon.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_type( $item ) {
		$icon_list = [
			'poll' => 'dashicons-chart-bar',
		];

		return sprintf( '<span tooltip="%s" flow="right"><span class="dashicons %s"></span></span>', ucfirst( $item['type'] ), $icon_list[ $item['type'] ] );
	}

	/**
	 * Render the column reference.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_reference( $item ) {
		if ( is_numeric( $item['reference'] ) ) {
			$id = $item['reference'];

			if ( ! empty( $id ) ) {
				$post_title = get_the_title( $item['reference'] );

				$actions = array(
					'edit' => sprintf( '<a href="%s" targe="_blank">' . __( 'Edit', 'poll-creator' ) . '</a>', get_edit_post_link( $id ) ),
					'view' => sprintf( '<a href="%s" targe="_blank">' . __( 'View frontend', 'poll-creator' ) . '</a>', get_permalink( $id ) ),
				);

				// Wrap the title with view result link.
				$title     = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $id ), $post_title );
				$reference = sprintf( '<strong>%1$s</strong> %2$s', $title, $this->row_actions( $actions ) );
			}
		} else {
			$reference = $item['reference'];
		}

		return $reference;
	}

	/**
	 * Render the column status.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$statuses = [
			'publish'  => __( 'Open', 'poll-creator' ),
			'draft'    => __( 'Closed', 'poll-creator' ),
			'schedule' => __( 'Schedule', 'poll-creator' ),
			'trash'    => __( 'Trash', 'poll-creator' ),
		];

		if ( 'schedule' === $item['status'] ) {
			$end_date = get_date_from_gmt( $item['settings']['endDate'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
			/* translators: %s: poll end date */
			$ended_text = sprintf( __( 'Ended at %s', 'poll-creator' ), $end_date );

			// Check if the poll ended.
			if ( strtotime( $item['settings']['endDate'] ) < time() ) {
				return sprintf( '<span tooltip="%s" flow="right" class="pollify-status status-%s">%s <span class="dashicons dashicons-info"></span></span>', $ended_text, 'draft', __( 'Closed', 'poll-creator' ) );
			}

			/* translators: %s: poll end date */
			$end_date_text = sprintf( __( 'Will be ended on %s', 'poll-creator' ), $end_date );

			return sprintf( '<span tooltip="%s" flow="right" class="pollify-status status-%s">%s <span class="dashicons dashicons-info"></span></span>', $end_date_text, $item['status'], $statuses[ $item['status'] ] );
		}

		// Wrap the status with span tag so later I can style it.
		return sprintf( '<span class="pollify-status status-%s">%s</span>', $item['status'], $statuses[ $item['status'] ] );
	}

	/**
	 * Render the column response.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_response( $item ) {
		// Get link for view-results page.
		$view_results_link = add_query_arg(
			[
				'page'    => 'poll-creator',
				'action'  => 'view_results',
				'tab'     => 'votes',
				'poll_id' => $item['client_id'],
			],
			admin_url( 'admin.php' )
		);

		ob_start();
		?>
		<div class="post-com-count-wrapper">
			<a href="<?php echo esc_url( $view_results_link ); ?>" class="post-com-count post-com-count-approved">
				<span class="comment-count-approved" aria-hidden="true"><?php echo esc_html( $item['response'] ) ?? 0; ?></span>
				<span class="screen-reader-text">
					<?php
						/* translators: %s: votes count */
						echo esc_html( wp_sprintf( __( '%s votes', 'poll-creator' ), $item['response'] ) );
					?>
				</span>
			</a>
		</div>
		<?php
		return ob_get_clean();
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
	 * Get the views for the table.
	 *
	 * @return array
	 */
	protected function get_views() {
		$views = [
			'all'     => '<a href="' . admin_url( 'admin.php?page=pollify' ) . '" class="' . ( empty( pollify_filter_input( INPUT_GET, 'status', POLLIFY_FILTER_SANITIZE_STRING ) ) ? 'current' : '' ) . '">' . __( 'All', 'poll-creator' ) . '</a>',
			'publish' => '<a href="' . admin_url( 'admin.php?page=pollify&status=publish' ) . '" class="' . ( 'publish' === pollify_filter_input( INPUT_GET, 'status', POLLIFY_FILTER_SANITIZE_STRING ) ? 'current' : '' ) . '">' . __( 'Open', 'poll-creator' ) . '</a>',
			'draft'   => '<a href="' . admin_url( 'admin.php?page=pollify&status=draft' ) . '" class="' . ( 'draft' === pollify_filter_input( INPUT_GET, 'status', POLLIFY_FILTER_SANITIZE_STRING ) ? 'current' : '' ) . '">' . __( 'Closed', 'poll-creator' ) . '</a>',
		];

		return $views;
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
			$type = pollify_filter_input( INPUT_POST, 'type', POLLIFY_FILTER_SANITIZE_STRING );
			?>
			<div class="alignleft actions bulkactions">
				<select name="type" id="poll-type" >
					<option value="all" <?php selected( 'all', $type, true ); ?>><?php esc_html_e( 'All types', 'poll-creator' ); ?></option>
					<option value="poll" <?php selected( 'poll', $type, true ); ?>><?php esc_html_e( 'Poll', 'poll-creator' ); ?></option>
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
		$this->per_page = $this->get_items_per_page( 'polls_per_page', 10 );

		// Set some default args for getting the table data.
		$args = [
			'per_page' => $this->per_page,
			'page'     => $this->get_pagenum(),
		];

		// Set type from extra nav filter.
		$type = pollify_filter_input( INPUT_POST, 'type', POLLIFY_FILTER_SANITIZE_STRING );

		if ( ! empty( $type ) ) {
			$args['type'] = $type;
		}

		// Set status if someone filter.
		$status = pollify_filter_input( INPUT_GET, 'status', POLLIFY_FILTER_SANITIZE_STRING ) ?: 'all';

		if ( ! empty( $status ) && in_array( $status, array_keys( $this->get_views() ), true ) ) {
			$args['status'] = $status;
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
	 * @param array $args The arguments for getting the data.
	 *
	 * @return array|int
	 */
	private function get_table_data( $args ) {
		return \wpRigel\Pollify\Polls::get_instance()->all( $args );
	}
}