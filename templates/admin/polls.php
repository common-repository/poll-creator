<?php
/**
 * Template for displaying all polls with actions
 *
 * @package pollify
 */

declare( strict_types = 1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reseted = pollify_filter_input( INPUT_GET, 'updated', FILTER_VALIDATE_BOOLEAN );
?>

<div class="wrap">
	<h2 class="wp-heading-inline">Polls</h2>

	<?php if ( $reseted ) : ?>
	<div id="message" class="notice is-dismissible updated">
		<p><?php esc_html_e( 'Poll results has been reseted.', 'poll-creator' ); ?></p>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'poll-creator' ); ?></span>
		</button>
	</div>
	<?php endif; ?>

	<?php
		$table = new \wpRigel\Pollify\Admin\PollsListTable();
		$table->views();

		echo '<form method="post">';

		// Prepare table.
		$table->prepare_items();

		// Search form.
		$table->search_box( __( 'Search by title', 'poll-creator' ), 'pollify_poll_search_id' );

		// Display table.
		$table->display();

		echo '</form>';
	?>
</div>