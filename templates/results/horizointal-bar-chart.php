<?php
/**
 * Template for displaying results as a
 * horizointal bar chart.
 *
 * @package pollify
 */

declare( strict_types = 1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = ! empty( $data ) ? $data : [];

?>
<div class="poll-answer-wrapper">
	<div class="horizointal-bar-chart">
		<?php if ( ! empty( $data['options'] ) ) : ?>
			<?php foreach ( $data['options'] as $result_option ) : ?>
			<div class="horizointal-bar-chart__bar">
				<div class="horizointal-bar-chart__bar-label">
					<span class="text">
						<?php echo wp_kses_post( $result_option['option'] ?? '' ); ?>
					</span>
					<span class="count">
						<?php
							/* translators: %s: votes count */
							echo esc_html( wp_sprintf( __( '%s votes', 'poll-creator' ), $result_option['votes'] ) );
						?>
					</span>
					<span class="percentage">
						<?php
							/* translators: %s: percentage */
							echo esc_html( wp_sprintf( __( '%s%', 'poll-creator' ), $result_option['percentage'] ) );
						?>
					</span>
				</div>
				<div class="horizointal-bar-chart__bar-indicator">
					<?php
					/* translators: %s: percentage */
					$percentage_style = wp_sprintf( __( '%s%', 'poll-creator' ), $result_option['percentage'] )
					?>
					<div class="bar-fill" style="width:<?php echo esc_html( $percentage_style ); ?>"></div>
				</div>
			</div>
			<?php endforeach; ?>
			<div class="horizointal-bar-chart__total-count">
				<span class="count">
					<?php
						/* translators: %s: total votes */
						echo esc_html( wp_sprintf( __( 'Total votes %s', 'poll-creator' ), $data['total_votes'] ) );
					?>
				</span>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No results found for this poll', 'poll-creator' ); ?></p>
		<?php endif; ?>
	</div>
</div>