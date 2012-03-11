<?php

class BP_Mega_Populate {
	function __construct() {
		require( dirname(__FILE__) . '/lorem.php' );
		$this->lorem = new BBG_Lorem_Ipsum;

		add_action( 'init', array( &$this, 'catcher' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
	}

	function catcher() {
		if ( !empty( $_GET['bpmp_submit'] ) || !empty( $_GET['amp;bpmp_submit'] ) ) {
			$this->process();
		}
	}

	function admin_menu() {
		$page = add_submenu_page(
			'tools.php',
			'BP Mega Populate',
			'BP Mega Populate',
			'manage_options',
			'bp-mega-populate',
			array( &$this, 'admin_markup' )
		);
	}

	function admin_markup() {
		if ( !is_super_admin() ) {
			return;
		}

		?>

		<div class="wrap">
			<h2>BP Mega Populate</h2>

			<form action="" method="get">
				<table class="form-table">
				<tbody>

				<tr>
					<th scope="row">
						Content type
					</th>

					<td>
						<select name="bpmp_content_type">
							<option value="activity"><?php _e( 'Activity', 'buddypress' ) ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						Number
					</th>

					<td>
						<input type="text" name="bpmp_number" />
					</td>
				</tr>

				</tbody>
				</table>

				<br /><br />
				<?php wp_nonce_field( 'bpmp' ) ?>
				<input type="submit" value="Submit" name="bpmp_submit" />
			</form>
		</div>

		<?php
	}

	function process() {
		foreach( $_GET as $gkey => $gvalue ) {
			$clean_key = str_replace( 'amp;', '', $gkey );
			$_GET[$clean_key] = $gvalue;
		}

		if ( empty( $_GET['_wpnonce'] ) || !wp_verify_nonce( $_GET['_wpnonce'], 'bpmp' ) ) {
			wp_die( 'No.' );
		}

		$url = add_query_arg( 'page', 'bp-mega-populate', admin_url( 'tools.php' ) );
		$url = add_query_arg( 'bpmp_submit', 'Submit', $url );

		$total_number = $_GET['bpmp_number'];
		$url = add_query_arg( 'bpmp_number', $total_number, $url );

		$content_type = $_GET['bpmp_content_type'] ? $_GET['bpmp_content_type'] : 'activity';
		$url = add_query_arg( 'bpmp_content_type', $content_type, $url );

		$url = wp_nonce_url( $url, 'bpmp' );

		$per_page = 800;

		$start = isset( $_GET['bpmp_start'] ) ? $_GET['bpmp_start'] : 0;
		$end   = $start + $per_page <= $total_number ? $start + $per_page : $total_number;

		if ( $start >= $total_number ) {
			return;
		}

		for ( $i = $start; $i <= $end; $i++ ) {
			switch( $content_type ) {
				case 'activity' :
					$this->create_activity();
					break;
			}
		}
		echo "Processed $start through $end<br />";

		$url = add_query_arg( 'bpmp_start', $end, $url );
		//echo $url; die();
		?>

		<script type="text/javascript">
			setTimeout( 'reload();', 2000 );
			function reload() {
				window.location = '<?php echo $url ?>';
			}
		</script>

		<?php
		die();
	}

	function create_activity() {
		global $bp;
		// Create some arguments
		$defaults = array(
			'id'                => false, // Pass an existing activity ID to update an existing entry.

			'action'            => '',    // The activity action - e.g. "Jon Doe posted an update"
			'content'           => '',    // Optional: The content of the activity item e.g. "BuddyPress is awesome guys!"

			'component'         => false, // The name/ID of the component e.g. groups, profile, mycomponent
			'type'              => false, // The activity type e.g. activity_update, profile_updated
			'primary_link'      => '',    // Optional: The primary URL for this item in RSS feeds (defaults to activity permalink)

			'user_id'           => bp_loggedin_user_id(), // Optional: The user to record the activity for, can be false if this activity is not for a user.
			'item_id'           => false, // Optional: The ID of the specific item being recorded, e.g. a blog_id
			'secondary_item_id' => false, // Optional: A second ID used to further filter e.g. a comment_id
			'recorded_time'     => bp_core_current_time(), // The GMT time that this activity was recorded
			'hide_sitewide'     => false, // Should this be hidden on the sitewide activity stream?
			'is_spam'           => false, // Is this activity item to be marked as spam?
		);

		// get a component item that actually has activity
		$component = 'settings';
		$no_ac_comps = array( 'settings', 'messages', 'xprofile' );
		while ( in_array( $component, $no_ac_comps ) ) {
			$component = array_rand( $bp->active_components );
		}

		$type = $primary_link = $action = '';
		
		$item_id = $secondary_item_id = 0;
		
		$content = $this->lorem->generate( 8, 250 );
		$user_id = $this->get_random_user_id();
		
		$recorded_time = $this->get_random_recorded_time();

		switch( $component ) {
			case 'activity' :
				$types = array(	'activity_update', 'activity_comment' );
				$key   = array_rand( $types );
				$type  = $types[$key];
				
				if ( $type == 'activity_comment' ) {
					$item_id = $this->get_random_activity_id();
					$secondary_item_id = $this->get_random_activity_parent( $item_id );
					$action = sprintf( __( '%s posted a new activity comment', 'buddypress' ), bp_core_get_userlink( $user_id ) );
					
				} else {					
					$from_user_link   = bp_core_get_userlink( $user_id );
					$action  = sprintf( __( '%s posted an update', 'buddypress' ), $from_user_link );					
					$primary_link = bp_core_get_userlink( $user_id, false, true );
				}

				break;

			case 'groups' :
				$types = array(	'created_group', 'joined_group', 'new_forum_post', 'new_forum_topic' );
				$key   = array_rand( $types );
				$type  = $types[$key];

				$item_id = $this->get_random_group_id();
				$group   = groups_get_group( array( 'group_id' => $item_id ) );
				$group_permalink = bp_get_group_permalink( $group );
				
				switch( $type ) {
					case 'created_group' :
						$action = sprintf( __( '%1$s created the group %2$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . $group_permalink . '">' . esc_attr( $group->name ) . '</a>' );
						$primary_link = $group_permalink;
						
						break;
					
					case 'joined_group' :
						$action = sprintf( __( '%1$s joined the group %2$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . $group_permalink . '">' . esc_attr( $group->name ) . '</a>' );
						$primary_link = $group_permalink;
						
						break;
					
					case 'new_forum_topic' :
						$topic_id = $this->get_random_topic_id();
						$topic = bp_forums_get_topic_details( $topic_id );
						
						$action = sprintf( __( '%1$s started the forum topic %2$s in the group %3$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . $group_permalink . 'forum/topic/' . $topic->topic_slug .'/">' . esc_attr( $topic->topic_title ) . '</a>', '<a href="' . $group_permalink . '">' . esc_attr( $group->name ) . '</a>' );
						
						$secondary_item_id = $topic_id;
						
						break;
					
					case 'new_forum_post' :
						$topic_id = $this->get_random_topic_id();
						$topic = bp_forums_get_topic_details( $topic_id );
						
						$action = sprintf( __( '%1$s replied to the forum topic %2$s in the group %3$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . $group_permalink . 'forum/topic/' . $topic->topic_slug .'/">' . esc_attr( $topic->topic_title ) . '</a>', '<a href="' . $group_permalink . '">' . esc_attr( $group->name ) . '</a>' );
						
						$secondary_item_id = $this->get_random_post_id( $topic_id );
						
						break;
				}


				break;

			case 'friends' :
				$type = 'friendship_created';
				$secondary_item_id = $this->get_random_user_id();
				$item_id = $this->get_random_friendship_id();
				
				$initiator_link = bp_core_get_userlink( $user_id );
				$friend_link = bp_core_get_userlink( $secondary_item_id );
				
				$action = sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), $friend_link, $initiator_link );
				
				break;

			case 'members' :
return;
				$types = array(	'activity_update', 'activity_comment' );
				$key   = array_rand( $types );
				$type  = $types[$key];
				break;

		}

		$args = array(
			'action'            => $action,
			'content'           => $content,
			'user_id'           => $user_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'component'         => $component,
			'type'              => $type,
			'primary_link'      => $primary_link,
			'recorded_time'     => $recorded_time
		);
		
		bp_activity_add( $args );
	}
	
	function get_random_recorded_time() {
		// Pick a date, any date
		$now = time();
		$timestamp = rand( 0, $now );
		return date( 'Y-m-d H:i:s', $timestamp );
	}

	function get_random_user_id() {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users ORDER BY RAND() LIMIT 1" ) );
	}
	
	/**
	 * Returns only root-level items
	 */
	function get_random_activity_id() {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$bp->activity->table_name} WHERE ( component != 'activity' OR item_id = '' OR item_id = 0 ) ORDER BY RAND() LIMIT 1" ) );
	}
	
	/**
	 * Select a random activity item from among the tree of a given root-level item
	 */
	function get_random_activity_parent( $activity_id ) {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$bp->activity->table_name} WHERE ( id = {$activity_id} OR ( type = 'new_activity_comment' AND item_id = {$activity_id} ) ) ORDER BY RAND() LIMIT 1" ) );
	}
	
	function get_random_group_id() {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name} ORDER BY RAND() LIMIT 1" ) );
	}
	
	function get_random_topic_id() {
		global $wpdb, $bp, $bbdb;

		do_action( 'bbpress_init' );

		return $wpdb->get_var( $wpdb->prepare( "SELECT topic_id FROM {$bbdb->topics} ORDER BY RAND() LIMIT 1" ) );
	}
	
	function get_random_post_id( $topic_id = 0 ) {
		global $wpdb, $bbdb;
		
		do_action( 'bbpress_init' );
		
		if ( $topic_id ) {
			$sql = $wpdb->prepare( "SELECT post_id FROM {$bbdb->posts} WHERE topic_id = {$topic_id} ORDER BY RAND() LIMIT 1" );
		} else {
			$sql = $wpdb->prepare( "SELECT post_id FROM {$bbdb->posts} ORDER BY RAND() LIMIT 1" );
		}
		
		return $wpdb->get_var( $sql );
	}
	
	function get_random_friendship_id() {
		global $wpdb, $bp;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->friends->table_name} ORDER BY RAND() LIMIT 1" ) );
	}
}
new BP_Mega_Populate;

?>