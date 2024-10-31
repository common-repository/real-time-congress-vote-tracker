<?php
/*
Plugin Name: Real Time Congress Vote Tracker
Plugin URI: http://imaginedc.net
Description: Widgetized display of voting taking place in Congress
Author: SidHarrell
Author URI: http://imaginedc.net
Version: 0.4
*/

$rtcvt_version = '0.4';
$rtcvt = new RealTimeCongressVoteTracker();

class RealTimeCongressVoteTracker
{

var $o; // options

/**
 * Constructor
 */	
function RealTimeCongressVoteTracker()
{
    if( !class_exists( 'WP_Http' ) )
        include_once( ABSPATH . WPINC. '/class-http.php' );
	// get options from DB
	$this->o = get_option('rtcvt');
	// widget
	add_action('widgets_init', array( &$this, 'register_widgets'));
	add_action('admin_menu', array( &$this, 'addOptionsPage'));
	// shortcode
	add_shortcode('REAL-TIME-CONGRESS-VOTE-TRACKER', array( &$this, 'shortcode'));
	// uninstall function
	if ( function_exists('register_uninstall_hook') )
		register_uninstall_hook(ABSPATH.PLUGINDIR.'/realtimecongress/realTimeCongressVoteTracker.php', array( &$this, 'uninstall')); 
	// settingslink on plugin page
	add_filter('plugin_action_links', array( &$this, 'pluginActions'), 10, 2);
	// add locale support
	if (defined('WPLANG') && function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('rtcvt-lang', false, 'realtimecongress/locale');
		
}

/**
 * creates rtcvt code
 *
 * @return string form code
 */
function showForm( $params = '' )
{
  	// get options from DB
	$this->o = get_option('rtcvt');

    $url = 'http://api.realtimecongress.org/api/v1/votes.json?apikey=';
    $request = new WP_Http;
    $result = $request->request( $url.$this->o['api_key']."&sections=question,vote_breakdown&per_page=5" );
    $json = $result['body'];
    $data = json_decode($json);
    $form = "";
    foreach ($data->votes as $vote) {
      $form .= "<h4 style=\"font-weight: bold;\">".$vote->question."</h4>";
      $form .= "<table><tr><td style=\"width: 50px;\"></td><td style=\"width: 50px;\">Yea</td><td style=\"width: 50px;\">Nay</td><td style=\"width: 50px;\">NV</tr>";
      $form .= "<tr><td>Dem</td><td>".$vote->vote_breakdown->party->D->Yea."</td><td>".$vote->vote_breakdown->party->D->Nay."</td><td>".$vote->vote_breakdown->party->D->{'Not Voting'}."</td></tr>";
      $form .= "<tr><td>Rep</td><td>".$vote->vote_breakdown->party->R->Yea."</td><td>".$vote->vote_breakdown->party->R->Nay."</td><td>".$vote->vote_breakdown->party->R->{'Not Voting'}."</td></tr>";
      $form .= "<tr><td>Total</td><td>".$vote->vote_breakdown->total->Yea."</td><td>".$vote->vote_breakdown->total->Nay."</td><td>".$vote->vote_breakdown->total->{'Not Voting'}."</td></tr>";
      $form .= "</table>";
    }
	return $form;
}


/**
 * shows options page
 */
function optionsPage()
{	
	global $rtcvt_version;
	if (!current_user_can('manage_options'))
		wp_die(__('Sorry, but you have no permissions to change settings.'));
		
	// save data
	if ( isset($_POST['rtcvt_save']) )
	{
    	$api_key = stripslashes($_POST['rtcvt_api_key']);
		$this->o = array(
		    'api_key'		=> $api_key
			);
		update_option('rtcvt', $this->o);
	}
		
	// show page
	?>
	<div id="poststuff" class="wrap">
		<h2>Real Time Congress Vote Tracker</h2>
		<div class="postbox">
		<h3><?php _e('Options', 'cpd') ?></h3>
		<div class="inside">
		
		<form action="options-general.php?page=RealTimeCongressVoteTracker" method="post">
	    <table class="form-table">
	    <tr>
			<th><?php _e('API Key:', 'tcf-lang')?></th>
			<td><input name="rtcvt_api_key" type="text" size="70" value="<?php echo $this->o['api_key'] ?>" /><br /><?php _e('To access the data stream you can obtain your key at: http://services.sunlightlabs.com/accounts/register/', 'rtcvt-lang'); ?></td>
		</tr>
		</table>
		<p class="submit">
			<input name="rtcvt_save" class="button-primary" value="<?php _e('Save Changes'); ?>" type="submit" />
		</p>
		</form>
		
	    </div>
	</div>
	
	<div class="postbox">
		<h3><?php _e('Contact', 'rtcvt-lang') ?></h3>
		<div class="inside">
			<p>
			Real Time Congress Vote Tracker: <code><?php echo $rtcvt_version ?></code><br />
			<?php _e('Bug? Problem? Question? Hint? Praise?', 'rtcvt-lang') ?><br />
			send email to sidney.harrell@gmail.com</p>
		</div>
	</div>
	
	</div>
	<?php
}

/**
 * adds admin menu
 */
function addOptionsPage()
{
	$menutitle = 'Real Time Congress Vote Tracker';
	add_options_page('Real Time Congress Vote Tracker', $menutitle, 9, 'RealTimeCongressVoteTracker', array( &$this, 'optionsPage'));
}

/**
 * parses parameters
 *
 * @param string $atts parameters
 */
function shortcode( $atts )
{
	return $this->showForm();
}

/**
 * clean up when uninstall
 */
function uninstall()
{
	delete_option('real_time_congress_vote_tracker');
}


/**
 * adds an action link to the plugins page
 */
function pluginActions($links, $file)
{
	if( $file == plugin_basename(__FILE__)
		&& strpos( $_SERVER['SCRIPT_NAME'], '/network/') === false ) // not on network plugin page
	{
		$link = '<a href="options-general.php?page=RealTimeCongressVoteTracker">'.__('Settings').'</a>';
		array_unshift( $links, $link );
	}
	return $links;
}

/**
 * calls widget class
 */
function register_widgets()
{
	register_widget('RealTimeCongressVoteTracker_Widget');
}

} // RTCVT class

class RealTimeCongressVoteTracker_Widget extends WP_Widget
{
	/**
	 * constructor
	 */	 
	function RealTimeCongressVoteTracker_Widget() {
		parent::WP_Widget('rtcvt_widget', 'Real Time Congress Vote Tracker', array('description' => 'Real Time Congress Vote Tracker'));	
	}
 
	/**
	 * display widget
	 */	 
	function widget( $args, $instance)
	{
		global $rtcvt;
		extract($args, EXTR_SKIP);
		$title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if ( !empty( $title ) )
			echo $before_title.$title.$after_title;
		echo $rtcvt->showForm( $instance );
		echo $after_widget;
	}
 
	/**
	 *	update/save function
	 */	 	
	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		foreach ( $this->fields as $f )
			$instance[strtolower($f)] = strip_tags($new_instance[strtolower($f)]);
		return $instance;
	}
 
	/**
	 *	admin control form
	 */	 	
	function form( $instance )
	{
		$default = array('title' => 'Real Time Congress Vote Tracker');
		$instance = wp_parse_args( (array) $instance, $default );
 
		foreach ( $this->fields as $field )
		{ 
			$f = strtolower( $field );
			$field_id = $this->get_field_id( $f );
			$field_name = $this->get_field_name( $f );
		}
	}
} // widget class

?>
