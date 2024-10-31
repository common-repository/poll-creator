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

$attributes   = ! empty( $attributes ) ? $attributes : [];
$poll_options = [];


// Filter poll options from attribute which value is empty.
$poll_options = array_filter(
	$attributes['options'],
	function ( $option ) {
		return ! empty( $option['option'] );
	}
);

if ( ! empty( $poll_options ) ) : ?>
	<div class="poll-options-wrapper">
		<?php foreach ( $poll_options as $option ) : ?>
			<div class="option">
				<div class="option-selector">
					<!-- If optionType is radio then input radio otherwise checkbox -->
					<?php if ( 'radio' === $attributes['optionType'] ) : ?>
						<input type="radio" name="poll-option" class="radio" id="option-<?php echo esc_attr( $option['option_id'] ); ?>" value="<?php echo esc_attr( $option['option_id'] ); ?>">
					<?php else : ?>
						<input type="checkbox" name="poll-option[]" class="checkbox" id="option-<?php echo esc_attr( $option['option_id'] ); ?>" value="<?php echo esc_attr( $option['option_id'] ); ?>" >
					<?php endif; ?>
				</div>
				<label class="option-label" for="option-<?php echo esc_attr( $option['option_id'] ); ?>">
					<?php echo wp_kses_post( $option['option'] ); ?>
				</label>
			</div>
		<?php endforeach; ?>
	</div>

<?php endif; ?>