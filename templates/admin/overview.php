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

$poll_id     = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );
$poll        = ! empty( $poll ) ? $poll : \wpRigel\Pollify\Polls::get_instance()->get( $poll_id );
$nav_tab     = pollify_filter_input( INPUT_GET, 'tab', POLLIFY_FILTER_SANITIZE_STRING ) ?: 'overview';
$navigations = pollify_poll_results_page_nav();
?>

<div class="wrap pollify-poll-details-wrap">
	<div class="heading-wrap">
		<h1 class="wp-heading-inline">
			<span><?php echo wp_kses_post( $poll->get_title() ); ?></span>
		</h1>
		<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'pollify' ], admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Back to list', 'poll-creator' ); ?>
		</a>
	</div>

	<div class="navigation">
		<ul>
			<?php foreach ( $navigations as $navigation ) : ?>
			<li <?php echo $navigation['slug'] === $nav_tab ? 'class="active"' : ''; ?>>
				<a href="<?php echo esc_url( $navigation['link'] ); ?>">
					<span class="icon dashicons <?php echo esc_attr( $navigation['icon'] ); ?>"></span>
					<span class="text"><?php echo wp_kses_post( $navigation['title'] ); ?></span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="details-content">
		<?php if ( 'overview' === $nav_tab ) : ?>
		<div class="meta-cards">
			<div class="meta-card-column">
				<div class="result-overview meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'At a glance', 'poll-creator' ); ?></h2>
					</div>

					<div class="meta-card-content">
						<div class="horizointal-bar-chart">
							<?php $poll_results = $poll->get_results(); ?>

							<?php if ( ! empty( $poll_results['options'] ) ) : ?>
								<?php foreach ( $poll_results['options'] as $result_option ) : ?>
								<div class="horizointal-bar-chart__bar">
									<div class="horizointal-bar-chart__bar-label">
										<span class="text"><?php echo wp_kses_post( $result_option['option'] ?? '' ); ?></span>
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
											$percentage_width = wp_sprintf( __( '%s%', 'poll-creator' ), $result_option['percentage'] );
										?>
										<div class="bar-fill" style="width:<?php echo esc_html( $percentage_width ); ?>"></div>
									</div>
								</div>
								<?php endforeach; ?>
								<div class="horizointal-bar-chart__total-count">
									<span class="count">
										<?php
											/* translators: %s: total votes */
											echo esc_html( wp_sprintf( __( 'Total votes: %s', 'poll-creator' ), $poll_results['total_votes'] ) );
										?>
									</span>
								</div>
							<?php else : ?>
								<p><?php esc_html_e( 'No results found for this poll', 'poll-creator' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="popular-location meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'Popular Location', 'poll-creator' ); ?></h2>
					</div>

					<div class="meta-card-content location-data">
						<?php
						$location_votes = $poll->get_ip_votes(
							[
								'per_page' => 20,
								'orderby'  => 'votes',
							]
						);

						$location_data = [ [ __( 'Country', 'poll-creator' ), __( 'Votes', 'poll-creator' ) ] ];

						foreach ( $location_votes as $location_vote ) {
							$location_data[] = [
								pollify_get_country_name( $location_vote['location'] ),
								intval( $location_vote['votes'] ),
							];
						}
						?>
						<div class="location-map">
							<div id="geo-chart-map" class="geo-chart-map" data-locations="<?php echo esc_attr( wp_json_encode( $location_data ) ); ?>" ></div>
						</div>
						<div class="location-list">
							<?php if ( ! empty( $location_votes ) ) : ?>
								<?php foreach ( $location_votes as $location_vote ) : ?>
									<div class="location">
										<div class="country">
											<?php if ( ! empty( $location_vote['location'] ) ) : ?>
											<span class="flag-icon fi fi-<?php echo esc_html( strtolower( $location_vote['location'] ) ); ?> fib"></span>
											<span class="country-name"><?php echo wp_kses_post( pollify_get_country_name( $location_vote['location'] ) ); ?></span>
											<?php else : ?>
											<span class="country-name"><?php esc_html_e( 'Unknown', 'poll-creator' ); ?></span>
											<?php endif; ?>
										</div>
										<div class="count"><?php echo esc_html( $location_vote['votes'] ); ?></div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="no-data-text"><?php esc_html_e( 'No location data found for this poll', 'poll-creator' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="ip-details meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'IP overviews', 'poll-creator' ); ?></h2>
					</div>

					<div class="meta-card-content ip-overview">
						<?php
						$location_votes = $poll->get_ip_votes(
							[
								'per_page' => 5,
							]
						);
						?>
						<div class="ip-data-list">
							<?php if ( ! empty( $location_votes ) ) : ?>
								<table class="ips-table wp-list-table widefat table-view-list">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Location', 'poll-creator' ); ?></th>
											<th><?php esc_html_e( 'IP Address', 'poll-creator' ); ?></th>
											<th><?php esc_html_e( 'Vote count', 'poll-creator' ); ?></th>
										</tr>
									</thead>
									<?php foreach ( $location_votes as $location_vote ) : ?>
										<tr>
											<td class="country">
												<?php if ( ! empty( $location_vote['location'] ) ) : ?>
												<span class="flag-icon fi fi-<?php echo esc_html( strtolower( $location_vote['location'] ) ); ?> fib"></span>
												<span class="country-name"><?php echo wp_kses_post( pollify_get_country_name( $location_vote['location'] ) ); ?></span>
												<?php else : ?>
												<span class="country-name"><?php esc_html_e( 'Unknown', 'poll-creator' ); ?></span>
												<?php endif; ?>
											</td>
											<td class="ip-address"><?php echo esc_html( $location_vote['ip'] ); ?></td>
											<td class="count"><?php echo esc_html( $location_vote['votes'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</table>

								<div class="see-more-link">
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											[
												'page'    => 'pollify',
												'action'  => 'view_results',
												'tab'     => 'ip-details',
												'poll_id' => $poll->get_client_id(),
											],
											admin_url( 'admin.php' )
										)
									);
									?>
												"><?php esc_html_e( 'See all IP\'s', 'poll-creator' ); ?> &#8594;</a>
								</div>
							<?php else : ?>
								<p class="no-data-text"><?php esc_html_e( 'No location data found for this poll', 'poll-creator' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
			<div class="meta-card-column secondary">
				<div class="latest-votes meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'Recent votes', 'poll-creator' ); ?></h2>
					</div>

					<div class="meta-card-content recent-votes">
						<?php
						$recent_votes = $poll->get_votes();
						?>
						<?php if ( ! empty( $recent_votes ) ) : ?>
						<ul class="vote-list">
							<?php
							foreach ( $recent_votes as $recent_vote ) {
								?>
								<li>
									<div class="vote-info">
										<?php
										$user_id = $recent_vote['user_id'] ?? 0;
										if ( $user_id ) {
											$user = get_user_by( 'ID', $user_id );
										}
										?>
										<?php if ( ! empty( $user ) ) : ?>
											<div class="user-name"><?php echo esc_html( $user->display_name ); ?></div>
										<?php else : ?>
											<div class="user-name"><?php esc_html_e( 'Guest', 'poll-creator' ); ?></div>
										<?php endif; ?>

										<div class="other-details">
											<?php if ( ! empty( $recent_vote['user_location'] ) ) : ?>
											<span class="flag-icon fi fi-<?php echo esc_html( strtolower( $recent_vote['user_location'] ) ); ?> fib"></span>
											<?php endif; ?>
											<span class="user-ip"><?php echo esc_html( $recent_vote['user_ip'] ); ?></span>
										</div>
									</div>
									<div class="vote-details">
										<span class="option"><?php echo wp_kses_post( $recent_vote['option'] ); ?></span>
										<span class="time"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $recent_vote['created_at'] ) ) ); ?></span>
									</div>
								</li>
								<?php
							}
							?>

							<li class="see-more-link">
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										[
											'page'    => 'pollify',
											'action'  => 'view_results',
											'tab'     => 'votes',
											'poll_id' => $poll->get_client_id(),
										],
										admin_url( 'admin.php' )
									)
								);
								?>
											"><?php esc_html_e( 'See all votes', 'poll-creator' ); ?> &#8594;</a>
							</li>
						</ul>
						<?php else : ?>
							<p class="no-data-text"><?php esc_html_e( 'No recent votes found for this poll', 'poll-creator' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php elseif ( 'votes' === $nav_tab ) : ?>
			<div class="votes-table">
				<form method="post">
				<?php
					$table = new \wpRigel\Pollify\Admin\VotesListTable( $poll );

					// Prepare table.
					$table->prepare_items();

					// Search form.
					$table->search_box( __( 'Search by IP', 'poll-creator' ), 'pollify_vote_search_id' );

					// Display table.
					$table->display();
				?>
				</form>
			</div>
		<?php elseif ( 'ip-details' === $nav_tab ) : ?>
			<div class="ips-table">
				<form method="post">
				<?php
					$table = new \wpRigel\Pollify\Admin\IPsListTable( $poll );

					// Prepare table.
					$table->prepare_items();

					// Search form.
					$table->search_box( __( 'Search by IP', 'poll-creator' ), 'pollify_ip_search_id' );

					// Display table.
					$table->display();
				?>
				</form>
			</div>
		<?php else : ?>
			<?php
				/**
				 * Load the results navigation content.
				 *
				 * @param string $nav_tab Navigation tab.
				 * @param array  $navigations Navigations.
				 */
				do_action( 'pollify_load_results_nav_content', $nav_tab, $navigations );
			?>
		<?php endif; ?>
	</div>
</div>