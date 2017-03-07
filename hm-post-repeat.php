<?php

/*
Plugin Name: Repeatable Posts
Description: Designate a post as repeatable and it'll be copied and re-published on your chosen interval.
Author: Human Made Limited
Author URI: http://hmn.md/
Version: 0.4
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: hm-post-repeat
Domain Path: /languages
*/

/*
Copyright Human Made Limited  (email : hello@hmn.md)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

namespace HM\Post_Repeat;

/**
 * Setup the actions and filters required by this class.
 */
add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\publish_box_ui', 11 );
add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\unpublish_box_ui', 10 );
add_action( 'save_post', __NAMESPACE__ . '\save_post_repeating_status', 10 );
add_action( 'save_post', __NAMESPACE__ . '\create_next_repeat_post', 11 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
add_filter( 'display_post_states', __NAMESPACE__ . '\post_states', 10, 2 );

/**
 * Enqueue the scripts and styles that are needed by this plugin.
 */
function enqueue_scripts( $hook ) {

	// Ensure we only load them on the edit post and add new post admin screens
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
		return;
	}

	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_dir_url = plugin_dir_url( __FILE__ );

	wp_enqueue_script( 'hm-post-repeat', $plugin_dir_url . 'hm-post-repeat.js', 'jquery', $plugin_data['Version'], true );
	wp_enqueue_style( 'hm-post-repeat', $plugin_dir_url . 'hm-post-repeat.css', array(), $plugin_data['Version'] );

}

/**
 * Output the Post Repeat UI that is shown in the Publish post meta box.
 *
 * The UI varies depending on whether the post is the original repeating post
 * or itself a repeat.
 */
function publish_box_ui() {

	if ( ! in_array( get_post_type(), repeating_post_types() ) ) {
		return;
	} ?>

	<div class="misc-pub-section misc-pub-hm-post-repeat">

		<span class="dashicons dashicons-controls-repeat"></span>

		<?php esc_html_e( 'Repeat:', 'hm-post-repeat' ); ?>

		<?php if ( is_repeat_post( get_the_id() ) ) : ?>

			<strong><?php printf( esc_html__( 'Repeat of %s', 'hm-post-repeat' ), '<a href="' . esc_url( get_edit_post_link( get_post()->post_parent ) ) . '">' . esc_html( get_the_title( get_post_field( 'post_parent', get_the_id() ) ) ) . '</a>' ); ?></strong>

		<?php else : ?>

			<?php $repeating_schedule = get_repeating_schedule( get_the_id() ); ?>
			<?php $is_repeating_post = is_repeating_post( get_the_id() ) && isset( $repeating_schedule ); ?>

			<strong><?php echo ! $is_repeating_post ? esc_html__( 'No', 'hm-post-repeat' ) : esc_html( $repeating_schedule['display'] ); ?></strong>

			<a href="#hm-post-repeat" class="edit-hm-post-repeat hide-if-no-js"><span aria-hidden="true"><?php esc_html_e( 'Edit', 'hm-post-repeat' ); ?></span> <span class="screen-reader-text"><?php esc_html_e( 'Edit Repeat Settings', 'hm-post-repeat' ); ?></span></a>

			<span class="hide-if-js" id="hm-post-repeat">

				<select name="hm-post-repeat">
					<option<?php selected( ! $is_repeating_post ); ?> value="no"><?php esc_html_e( 'No', 'hm-post-repeat' ); ?></option>
					<?php foreach ( get_repeating_schedules() as $schedule_slug => $schedule ) : ?>
						<option<?php selected( $is_repeating_post && $schedule_slug === $repeating_schedule['slug'] ); ?> value="<?php echo esc_attr( $schedule_slug ); ?>"><?php echo esc_html( $schedule['display'] ); ?></option>
					<?php endforeach; ?>
				</select>

				<a href="#hm-post-repeat" class="save-post-hm-post-repeat hide-if-no-js button"><?php esc_html_e( 'OK', 'hm-post-repeat' ); ?></a>

			</span>

		<?php endif; ?>

	</div>

<?php }

/**
 * Output the Post Unpublish UI that is shown in the Publish post meta box.
 */
function unpublish_box_ui() {

	if ( ! in_array( get_post_type(), repeating_post_types() ) ) {
		return;
	} ?>

    <div class="misc-pub-section misc-pub-hm-post-unpublish">

        <span class="dashicons dashicons-calendar-alt"></span>

		<?php esc_html_e( 'Unpublish', 'hm-post-repeat' ); ?>

        <?php
        $unpublish_timestamp     = get_unpublish_timestamp( get_the_id() );
        $has_unpublish_timestamp = ! empty( $unpublish_timestamp );
        if ( $has_unpublish_timestamp ) {
	        $local_timestamp = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $unpublish_timestamp ) ) );
	        /* translators: for date format, see https://secure.php.net/date */
	        $datetime_format = __( 'M j, Y @ H:i', 'hm-post-repeat' );
	        $unpublish_date  = date_i18n( $datetime_format, $local_timestamp );
	        $date_parts      = array(
		        'day'    => date( 'd', $local_timestamp ),
		        'month'  => date( 'm', $local_timestamp ),
		        'year'   => date( 'Y', $local_timestamp ),
		        'hour'   => date( 'H', $local_timestamp ),
		        'minute' => date( 'i', $local_timestamp )
	        );
        } else {
	        $unpublish_date = '&mdash;';
	        $date_parts     = array(
		        'day'    => '',
		        'month'  => '',
		        'year'   => '',
		        'hour'   => '',
		        'minute' => ''
	        );
        }

        ?>

        <strong><?php echo $has_unpublish_timestamp ? esc_html( $unpublish_date['display'] ) : esc_html__( 'never', 'hm-post-repeat' ); ?></strong>

        <a href="#hm-post-unpublish" class="edit-hm-post-unpublish hide-if-no-js"><span aria-hidden="true"><?php esc_html_e( 'Edit', 'hm-post-repeat' ); ?></span> <span class="screen-reader-text"><?php esc_html_e( 'Edit Repeat Settings', 'hm-post-repeat' ); ?></span></a>

        <div class="hide-if-js" id="hm-post-unpublish">

            <label>
				<span class="screen-reader-text"><?php esc_html_e( 'Month', 'hm-post-repeat' ); ?></span>
                <select name="hm-post-unpublish-month" id="" class="hm-post-unpublish-month">
                    <option value=""><?php esc_html_e( '&mdash;', 'hm-post-repeat' ); ?></option>
                    <?php
                    global $wp_locale;
                    for ( $i = 1; $i < 13; $i++ ) {
                        $month_num = zeroise( $i, 2);
                        $month_text = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
                        ?>
                        <option value="<?php echo $month_num; ?>" <?php selected($date_parts['month'], $month_num); ?>><?php echo $month_text?></option>
                    <?php
                    }
                    ?>
                </select>
            </label>
            <label>
				<span class="screen-reader-text"><?php esc_html_e( 'Day', 'hm-post-repeat' ); ?></span>
				<input id="hm-post-unpublish-day" name="hm-post-unpublish-day" size="2" maxlength="2" autocomplete="off" type="text" value="<?php echo esc_attr( $date_parts['day'] ); ?>" />
			</label>
            ,
            <label>
				<span class="screen-reader-text"><?php esc_html_e( 'Year', 'hm-post-repeat' ); ?></span>
				<input id="hm-post-unpublish-year" name="hm-post-unpublish-year" size="4" maxlength="4" autocomplete="off" type="text" value="<?php echo esc_attr( $date_parts['year'] ); ?>" />
			</label>
			@
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Hour', 'hm-post-repeat' ); ?></span>
				<input id="hm-post-unpublish-hour" name="hm-post-unpublish-hour" size="2" maxlength="2" autocomplete="off" type="text" value="<?php echo esc_attr( $date_parts['hour'] ); ?>" />
			</label>
			:
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Minute', 'hm-post-repeat' ); ?></span>
				<input id="hm-post-unpublish-minute" name="hm-post-unpublish-minute" size="2" maxlength="2" autocomplete="off" type="text" value="<?php echo esc_attr( $date_parts['minute'] ); ?>" />
			</label>

            <p>
                <a href="#hm-post-unpublish" class="save-post-hm-post-unpublish hide-if-no-js button"><?php esc_html_e( 'OK', 'hm-post-repeat' ); ?></a>
                <a href="#hm-post-unpublish" class="cancel-post-hm-post-unpublish hide-if-no-js button-cancel"><?php esc_html_e( 'Cancel', 'hm-post-repeat' ); ?></a>
            </p>

        </div>

    </div>

<?php }

/**
 * Add some custom post states to cover repeat and repeating posts.
 *
 * By default post states are displayed on the Edit Post screen in bold after the post title.
 *
 * @param array   $post_states The original array of post states.
 * @param WP_Post $post        The post object to get / return the states.
 * @return array The array of post states with ours added.
 */
function post_states( $post_states, $post ) {

	if ( is_repeating_post( $post->ID ) ) {

		// If the schedule has been removed since publishing, let the user know.
		if ( get_repeating_schedule( $post->ID ) ) {
			$post_states['hm-post-repeat'] = __( 'Repeating', 'hm-post-repeat' );
		} else {
			$post_states['hm-post-repeat'] = __( 'Invalid Repeating Schedule', 'hm-post-repeat' );
		}

	}

	if ( is_repeat_post( $post->ID ) ) {
		$post_states['hm-post-repeat'] = __( 'Repeat', 'hm-post-repeat' );
	}

	return $post_states;

}

/**
 * Save the repeating status to post meta.
 *
 * Hooked into `save_post`. When saving a post that has been set to repeat we save a post meta entry.
 *
 * @param int    $post_id             The ID of the post.
 * @param string $post_repeat_setting Used to manually set the repeating schedule from tests.
 */
function save_post_repeating_status( $post_id = null, $post_repeat_setting = null ) {

	if ( is_null( $post_repeat_setting ) ) {
		$post_repeat_setting = isset( $_POST['hm-post-repeat'] ) ? sanitize_text_field( $_POST['hm-post-repeat'] ) : '';
	}

	if ( ! in_array( get_post_type( $post_id ), repeating_post_types() ) || empty( $post_repeat_setting ) ) {
		return;
	}

	if ( 'no' === $post_repeat_setting ) {
		delete_post_meta( $post_id, 'hm-post-repeat' );
	}

	// Make sure we have a valid schedule.
	elseif ( in_array( $post_repeat_setting, array_keys( get_repeating_schedules() ) ) ) {
		update_post_meta( $post_id, 'hm-post-repeat', $post_repeat_setting );
	}

}


/**
 * Create the next repeat post when the last one is published.
 *
 * When a repeat post (or the original) is published we copy and schedule a new post
 * to publish on the correct interval. That way the next repeat post is always ready to go.
 * This is hooked into publish_post so that the repeat post is only created when the original
 * is published.
 *
 * @param int $post_id The ID of the post.
 */
function create_next_repeat_post( $post_id ) {

	if ( ! in_array( get_post_type( $post_id ), repeating_post_types() ) ) {
		return false;
	}

	if ( 'publish' !== get_post_status( $post_id ) ) {
		return false;
	}

	$original_post_id = get_repeating_post( $post_id );

	// Bail if we're not publishing a repeat(ing) post
	if ( ! $original_post_id ) {
		return false;
	}

	$original_post = get_post( $original_post_id, ARRAY_A );

	// If there is already a repeat post scheduled don't create another one
	if ( get_next_scheduled_repeat_post( $original_post['ID'] ) ) {
		return false;
	}

	// Bail if the saved schedule doesn't exist
	$repeating_schedule = get_repeating_schedule( $original_post['ID'] );

	if ( ! $repeating_schedule ) {
		return false;
	}

	// Bail if the original post isn't already published
	if ( 'publish' !== $original_post['post_status'] ) {
		return false;
	}

	$next_post = $original_post;

	// Create the repeat post as a copy of the original, but ignore some fields
	unset( $next_post['ID'] );
	unset( $next_post['guid'] );
	unset( $next_post['post_date_gmt'] );
	unset( $next_post['post_modified'] );
	unset( $next_post['post_modified_gmt'] );

	// We set the post_parent to the original post_id, so they're related
	$next_post['post_parent'] = $original_post['ID'];

	// Set the next post to publish in the future
	$next_post['post_status'] = 'future';

	// Use the date of the current post being saved as the base
	$next_post['post_date'] = date( 'Y-m-d H:i:s', strtotime( get_post_field( 'post_date', $post_id ) . ' + ' . $repeating_schedule['interval'] ) );

	// Make sure the next post will be in the future
	if ( strtotime( $next_post['post_date'] ) <= time() ) {
		return false;
	}

	// All checks done, get that post scheduled!
	$next_post_id = wp_insert_post( wp_slash( $next_post ), true );

	if ( is_wp_error( $next_post_id ) ) {
		return false;
	}

	// Mirror any post_meta
	$post_meta = get_post_meta( $original_post['ID'] );

	if ( $post_meta  ) {

		// Ignore some internal meta fields
		unset( $post_meta['_edit_lock'] );
		unset( $post_meta['_edit_last'] );

		// Don't copy the post repeat meta as only the original post should have that
		unset( $post_meta['hm-post-repeat'] );

		foreach ( $post_meta as $key => $values ) {
			foreach ( $values as $value ) {
				add_post_meta( $next_post_id, $key, maybe_unserialize( $value ) );
			}
		}
	}

	// Mirror any term relationships
	$taxonomies = get_object_taxonomies( $original_post['post_type'] );

	foreach ( $taxonomies as $taxonomy ) {
		wp_set_object_terms( $next_post_id, wp_list_pluck( wp_get_object_terms( $original_post['ID'], $taxonomy ), 'slug' ), $taxonomy );
	}

	return $next_post_id;

}

/**
 * The post types the feature is enabled on
 *
 * By default only posts have the feature enabled but others can be added with the `hm_post_repeat_post_types` filter.
 *
 * @return array An array of post types
 */
function repeating_post_types() {

	/**
	 * Enable support for additional post types.
	 *
	 * @param string[] $post_types Post type slugs.
	 */
	return apply_filters( 'hm_post_repeat_post_types', array( 'post' ) );

}

/**
 * All available repeat schedules.
 *
 * @return array An array of all available repeat schedules
 */
function get_repeating_schedules() {

	/**
	 * Enable support for additional schedules.
	 *
	 * @param array[] $schedules Schedule array items.
	 */
	$schedules = apply_filters( 'hm_post_repeat_schedules', array(
		'daily'   => array( 'interval' => '1 day',   'display' => __( 'Daily',   'hm-post-repeat' ) ),
		'weekly'  => array( 'interval' => '1 week',  'display' => __( 'Weekly',  'hm-post-repeat' ) ),
		'monthly' => array( 'interval' => '1 month', 'display' => __( 'Monthly', 'hm-post-repeat' ) ),
		'yearly' => array( 'interval' => '1 year', 'display' => __( 'Yearly', 'hm-post-repeat' ) )
	) );

	foreach ( $schedules as $slug => &$schedule ) {
		$schedule['slug'] = $slug;
	}

	return $schedules;

}

/**
 * Get the unpublish timestamp of the given post_id.
 *
 * @param int $post_id The id of the post you want to check.
 * @return array|null The timestamp of the unpublish date/time, or null if invalid.
 */
function get_unpublish_timestamp( $post_id ) {

	$unpublish_timestamp = get_post_meta( $post_id, 'hm-post-unpublish', true );

	return $unpublish_timestamp;
}

/**
 * Get the repeating schedule of the given post_id.
 *
 * @param int $post_id The id of the post you want to check.
 * @return array|null The schedule to repeat by, or null if invalid.
 */
function get_repeating_schedule( $post_id ) {

	if ( ! is_repeating_post( $post_id ) ) {
		return;
	}

	$repeating_schedule = get_post_meta( $post_id, 'hm-post-repeat', true );
	$schedules = get_repeating_schedules();

	// Backwards compatibility with 0.3 when we only supported weekly
	if ( '1' === $repeating_schedule ) {
		$repeating_schedule = 'weekly';
	}

	if ( array_key_exists( $repeating_schedule, $schedules ) ) {
		return $schedules[ $repeating_schedule ];
	}

}

/**
 * Check whether a given post_id is a repeating post.
 *
 * A repeating post is defined as the original post that was set to repeat.
 *
 * @param int $post_id The id of the post you want to check.
 * @return bool Whether the passed post_id is a repeating post or not.
 */
function is_repeating_post( $post_id ) {

	// We check $_POST data so that this function works inside a `save_post` hook when the post_meta hasn't yet been saved
	if ( isset( $_POST['hm-post-repeat'] ) && isset( $_POST['ID'] ) && $_POST['ID'] === $post_id ) {
		return true;
	}

	if ( get_post_meta( $post_id, 'hm-post-repeat', true ) ) {
		return true;
	}

	return false;

}

/**
 * Check whether a given post_id is a repeat post.
 *
 * A repeat post is defined as any post which is a repeat of the original repeating post.
 *
 * @param int $post_id The id of the post you want to check.
 * @return bool Whether the passed post_id is a repeat post or not.
 */
function is_repeat_post( $post_id ) {

	$post_parent = get_post_field( 'post_parent', $post_id );

	if ( $post_parent && get_post_meta( $post_parent, 'hm-post-repeat', true ) ) {
		return true;
	}

	return false;

}

/**
 * Get the next scheduled repeat post
 *
 * @param int $post_id The id of a repeat or repeating post
 * @return Int|Bool Return the ID of the next repeat post_id or false if it can't find one
 */
function get_next_scheduled_repeat_post( $post_id ) {

	$post = get_post( get_repeating_post( $post_id ) );

	$repeat_posts = get_posts( array( 'post_status' => 'future', 'post_parent' => $post->ID ) );

	if ( isset( $repeat_posts[0] ) ) {
	 	return $repeat_posts[0];
	}

	return false;

}

/**
 * Get the next scheduled repeat post
 *
 * @param int $post_id The id of a repeat or repeating post
 * @return Int|Bool Return the original repeating post_id or false if it can't find it
 */
function get_repeating_post( $post_id ) {

	$original_post_id = false;

	// Are we publishing a repeat post
	if ( is_repeat_post( $post_id ) ) {
		$original_post_id = get_post( $post_id )->post_parent;
	}

	// Or the original
	elseif ( is_repeating_post( $post_id ) ) {
		$original_post_id = $post_id;
	}

	return $original_post_id;

}
