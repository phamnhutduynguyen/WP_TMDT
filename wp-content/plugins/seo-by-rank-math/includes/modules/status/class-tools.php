<?php
/**
 * The Tools Class.
 *
 * @since      1.0.33
 * @package    RankMath
 * @subpackage RankMath\Status
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Status;

use RankMath\Helper;
use RankMath\Traits\Hooker;

/**
 * Tools class.
 */
class Tools {

	use Hooker;

	/**
	 * Register tools rest api hooks.
	 */
	public function hooks() {
		foreach ( $this->get_tools() as $id => $tool ) {
			add_filter( 'rank_math/tools/' . $id, [ $this, $id ] );
		}
	}

	/**
	 * Display Tools data.
	 */
	public function display() {
		?>
		<table class='rank-math-status-table striped rank-math-tools-table widefat'>

			<tbody class='tools'>

				<?php foreach ( $this->get_tools() as $id => $tool ) : ?>
					<tr class='<?php echo sanitize_html_class( $id ); ?>'>
						<th>
							<strong class='name'><?php echo esc_html( $tool['title'] ); ?></strong>
							<p class='description'><?php echo $tool['description']; ?></p>
						</th>
						<td class='run-tool'>
							<a href='#' class='button button-large tools-action' data-action='<?php echo esc_attr( $id ); ?>' data-confirm="<?php echo isset( $tool['confirm_text'] ) ? esc_attr( $tool['confirm_text'] ) : 'false'; ?>"><?php echo esc_html( $tool['button_text'] ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>

			</tbody>

		</table>
		<?php
	}

	/**
	 * Get tools.
	 *
	 * @return array
	 */
	private function get_tools() {
		$tools = [
			'clear_transients'    => [
				'title'       => __( 'Rank Math transients', 'rank-math' ),
				'description' => __( 'This tool will clear all the transients created by the Rank Math.', 'rank-math' ),
				'button_text' => __( 'Clear transients', 'rank-math' ),
			],

			'clear_seo_analysis'  => [
				'title'       => __( 'Clear seo analysis data', 'rank-math' ),
				'description' => __( 'This tool will clear the SEO Analysis data.', 'rank-math' ),
				'button_text' => __( 'Clear SEO Analysis', 'rank-math' ),
			],

			'delete_links'        => [
				'title'        => __( 'Delete Internal Links data', 'rank-math' ),
				'description'  => __( 'This option will delete ALL the Internal Links data.', 'rank-math' ),
				'confirm_text' => __( 'Are you sure you want to delete Internal Links Data? This action is irreversible.', 'rank-math' ),
				'button_text'  => __( 'Delete Internal Links', 'rank-math' ),
			],

			'delete_redirections' => [
				'title'        => __( 'Delete Redirections rule', 'rank-math' ),
				'description'  => __( 'This option will delete ALL Redirection rules.', 'rank-math' ),
				'confirm_text' => __( 'Are you sure you want to delete all the Redirection Rules? This action is irreversible.', 'rank-math' ),
				'button_text'  => __( 'Delete Redirections', 'rank-math' ),
			],

			'delete_log'          => [
				'title'        => __( 'Delete 404 Log', 'rank-math' ),
				'description'  => __( 'This option will delete ALL 404 monitor log.', 'rank-math' ),
				'confirm_text' => __( 'Are you sure you want to delete the 404 log? This action is irreversible.', 'rank-math' ),
				'button_text'  => __( 'Delete 404 Log', 'rank-math' ),
			],
		];

		if ( ! empty( Helper::get_review_posts() ) ) {
			$tools['convert_review'] = [
				'title'        => __( 'Convert Review Schema into Article', 'rank-math' ),
				/* translators: 1. Google documentation link 2. Filter code */
				'description'  => sprintf( __( 'Before using this converter, please read our Knowledge Base Article from %s.', 'rank-math' ), '<a href="https://rankmath.com/kb/how-to-fix-review-schema-errors/" target="_blank">' . __( 'here', 'rank-math' ) . '</a>' ),
				/* translators: Number of posts to update */
				'confirm_text' => sprintf( __( 'Are you sure you want to covert %s posts with review schema into new schema type? This action is irreversible.', 'rank-math' ), count( Helper::get_review_posts() ) ),
				'button_text'  => __( 'Convert', 'rank-math' ),
			];
		}

		return $tools;
	}

	/**
	 * Function to clear all the transients.
	 */
	public function clear_transients() {
		global $wpdb;

		$transients = $wpdb->get_col(
			"SELECT `option_name` AS `name`
			FROM  $wpdb->options
			WHERE `option_name` LIKE '%_transient_rank_math%'
			ORDER BY `option_name`"
		);

		if ( empty( $transients ) ) {
			return;
		}

		foreach ( $transients as $transient ) {
			delete_option( $transient );
		}

		return __( 'Rank Math transients cleared.', 'rank-math' );
	}

	/**
	 * Function to reset SEO Analysis.
	 */
	public function clear_seo_analysis() {
		delete_option( 'rank_math_seo_analysis_results' );

		return  __( 'SEO Analysis data successfully deleted.', 'rank-math' );
	}

	/**
	 * Function to delete the Internal Links data.
	 */
	public function delete_links() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rank_math_internal_links;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rank_math_internal_meta;" );

		return __( 'Internal Links successfully deleted.', 'rank-math' );
	}

	/**
	 * Function to delete 404 log.
	 */
	public function delete_log() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rank_math_404_logs;" );

		return __( '404 Log successfully deleted.', 'rank-math' );
	}

	/**
	 * Function to delete the Redirections data.
	 */
	public function delete_redirections() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rank_math_redirections;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}rank_math_redirections_cache;" );

		return __( 'Redirection rules successfully deleted.', 'rank-math' );
	}

	/**
	 * Function to convert the Review schema type.
	 */
	public function convert_review() {
		$posts = Helper::get_review_posts();
		if ( empty( $posts ) ) {
			return __( 'No review posts found.', 'rank-math' );
		}

		$count = 0;
		foreach ( $posts as $post_id ) {
			update_post_meta( $post_id, 'rank_math_rich_snippet', $this->do_filter( 'convert_review/type', 'article', $post_id ) );
			update_post_meta( $post_id, 'rank_math_snippet_article_type', $this->do_filter( 'convert_review/article_type', 'BlogPosting', $post_id ) );
			$count++;
		}
		update_option( 'rank_math_review_posts', $posts );
		/* translators: Number of posts updated */
		return sprintf( __( '%1$s review Posts updated. You can find the list of all converted posts %2$s.', 'rank-math' ), $count, '<a href="' . esc_url( admin_url( 'edit.php?post_type=post&review_posts=1' ) ) . '" target="_blank">here</a>' );
	}
}
