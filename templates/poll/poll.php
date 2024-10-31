<?php
/**
 * Poll template for frontend rendering.
 *
 * This template can be overridden by copying it to yourtheme/pollify/poll.php.
 *
 * @var array $attributes
 *
 * @package wpRigel\Pollify
 *
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attributes = ! empty( $attributes ) ? $attributes : [];

$styles = '';

if ( ! empty( $attributes['submitButtonBgColor'] ) ) {
	$styles .= '--pollify-submit-button-bg-color: ' . $attributes['submitButtonBgColor'] . ';';
}

if ( ! empty( $attributes['submitButtonBgColor'] ) ) {
	$styles .= '--pollify-submit-button-bg-color: ' . $attributes['submitButtonBgColor'] . ';';
}

if ( ! empty( $attributes['submitButtonTextColor'] ) ) {
	$styles .= '--pollify-submit-button-text-color: ' . $attributes['submitButtonTextColor'] . ';';
}

if ( ! empty( $attributes['submitButtonHoverTextColor'] ) ) {
	$styles .= '--pollify-submit-button-hover-text-color: ' . $attributes['submitButtonHoverTextColor'] . ';';
}

if ( ! empty( $attributes['submitButtonHoverBgColor'] ) ) {
	$styles .= '--pollify-submit-button-hover-bg-color: ' . $attributes['submitButtonHoverBgColor'] . ';';
}

if ( ! empty( $attributes['closingBannerBgColor'] ) ) {
	$styles .= '--pollify-closing-banner-bg-color: ' . $attributes['closingBannerBgColor'] . ';';
}

if ( ! empty( $attributes['closingBannerTextColor'] ) ) {
	$styles .= '--pollify-closing-banner-text-color: ' . $attributes['closingBannerTextColor'] . ';';
}

// Check if the poll status is draft and close poll status is `hide-poll` then return.
if ( 'draft' === $attributes['status'] && 'hide-poll' === $attributes['closePollState'] ) {
	return;
}

// If poll status is schedule and $attribute['endDate'] is less than to current time and close poll status is hide-poll then return.
if ( 'schedule' === $attributes['status'] && strtotime( $attributes['endDate'] ) < time() && 'hide-poll' === $attributes['closePollState'] ) {
	return;
}

$is_draft_with_show_results    = ( 'draft' === $attributes['status'] && 'show-result' === $attributes['closePollState'] );
$is_schedule_with_show_results = ( 'schedule' === $attributes['status'] && strtotime( $attributes['endDate'] ) < time() && 'show-result' === $attributes['closePollState'] );

$is_draft_with_show_close_banner    = ( 'draft' === $attributes['status'] && 'show-message' === $attributes['closePollState'] );
$is_schedule_with_show_close_banner = ( 'schedule' === $attributes['status'] && strtotime( $attributes['endDate'] ) < time() && 'show-message' === $attributes['closePollState'] );

$voter            = new \wpRigel\Pollify\Model\Voter();
$results          = \wpRigel\Pollify\Votes::get_instance()->get_results( $attributes['pollClientId'] );
$is_already_voted = ( ! empty( $attributes['allowedPerComputerResponse'] ) && $voter->is_already_voted( $attributes['pollClientId'] ) );
?>
<div
<?php
echo wp_kses(
	get_block_wrapper_attributes( [ 'style' => esc_attr( $styles ) ] ),
	array(
		'class' => array(),
		'style' => array(),
	)
);
?>
>
	<div class='pollify-poll-form'>
		<h4 class="poll-title rich-text"><?php echo wp_kses_post( $attributes['title'] ); ?></h4>

		<?php if ( ! empty( $attributes['description'] ) ) : ?>
			<p class="poll-description rich-text"><?php echo esc_html( $attributes['description'] ); ?></p>
		<?php endif; ?>

		<?php if ( $is_draft_with_show_results || $is_schedule_with_show_results ) : ?>
			<?php
				pollify_load_template(
					'results/horizointal-bar-chart.php',
					false,
					[
						'data' => $results,
					]
				)
			?>
		<?php else : ?>
			<?php if ( ! ( $is_already_voted || $is_draft_with_show_close_banner || $is_schedule_with_show_close_banner ) ) : ?>
			<form action="post" class="poll-form">
				<?php
					pollify_load_template(
						'poll/options.php',
						false,
						[
							'attributes' => $attributes,
						]
					)
				?>

				<div class="wp-block-button poll-block-button align-<?php echo esc_attr( $attributes['submitButtonAlign'] ); ?>">
					<div class="submit-button-wrapper has-custom-width wp-block-button-width-<?php echo esc_attr( $attributes['submitButtonWidth'] ); ?>"">
						<input type="hidden" name="poll-client-id" value="<?php echo esc_attr( $attributes['pollClientId'] ); ?>">
						<input type="submit" class="wp-block-button__link submit-button" value="<?php echo esc_html( $attributes['submitButtonLabel'] ); ?>" />
					</div>
				</div>
			</form>
			<?php else : ?>
				<?php
				if ( ! ( $is_draft_with_show_close_banner || $is_schedule_with_show_close_banner ) && ( $is_already_voted && ! empty( $attributes['confirmationMessageType'] ) && 'view-result' === $attributes['confirmationMessageType'] ) ) {
					pollify_load_template(
						'results/horizointal-bar-chart.php',
						false,
						[
							'data' => $results,
						]
					);
				} else {
					pollify_load_template(
						'poll/options.php',
						false,
						[
							'attributes' => $attributes,
						]
					);
				}
				?>
				<?php if ( $is_draft_with_show_close_banner || $is_schedule_with_show_close_banner ) : ?>
					<div class="closing-banner">
						<p>
							<?php
								echo wp_kses_post( $attributes['closePollmessage'] ?? __( 'This poll is closed', 'poll-creator' ) );
							?>
						</p>
					</div>
				<?php else : ?>
					<?php if ( $is_already_voted ) : ?>
						<div class="response-message">
							<?php
								echo (
									! empty( $attributes['confirmationMessageType'] )
									&& 'view-message' === $attributes['confirmationMessageType']
								) ? esc_html( $attributes['confirmationMessage'] ) : esc_html__( 'Thank you for voting!', 'poll-creator' );
							?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>