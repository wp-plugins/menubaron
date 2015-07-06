<?php
/**
 * @package MenuBaron_WP
 */
/*
Plugin Name: MenuBaron for WordPress
Plugin URI: http://menubaron.com
Description: Display MenuBaron.com powered online restaurant menus on your restaurant's WordPress site.
Author: MenuBaron
Version: 1.0.2
Author URI: http://menubaron.com
*/

wp_enqueue_script('jquery-ui', plugins_url('js/jquery-ui-1.10.4.custom.min.js',__FILE__), array('jquery'), '1.8.6');

if ( is_admin() ){ // admin actions
  add_action('admin_menu', 'mb_plugin_menu');
  add_action('admin_init', 'register_mbsettings');
} 

function mb_plugin_menu() {
	add_menu_page('MenuBaron Plugin Settings', 'MenuBaron', 'manage_options', 'menubaron-options', 'mb_options_page', plugins_url('/favicon.ico', __FILE__));
	add_submenu_page('menubaron-options','MenuBaron for WordPress FAQ','FAQ','manage_options','edit_pages','mb_wp_faq');
}

function register_mbsettings() { // whitelist options
  register_setting( 'mb-option-group', 'mb_apikey' );
  register_setting( 'mb-option-group', 'mb_specials_category' );
  register_setting( 'mb-option-group', 'mb_widget_showimage' );
  register_setting( 'mb-option-group', 'mb_menu_showprices' );
  register_setting( 'mb-option-group', 'mb_menu_showdesc' );
}

function mb_options_page(){
	$kp = false;
	$kv = true;
	$a = get_option('mb_apikey'); if(!($a===false)&&strlen($a)>1){ 
		$kp=true;
	}
	if($kp){
		$response = wp_remote_get( 'http://api.menubaron.com/utensils/menu.php?apikey='.get_option('mb_apikey') );  
		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			$kv = true;
		} 
		$d = wp_remote_retrieve_body( $response );
		$j = json_decode($d,true);
		if(count($j)>0){
			$kv = false;
		}
	}
?>
<div class="wrap">
<h2>MenuBaron for WordPress Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'mb-option-group' ); ?>
    <?php do_settings_sections( 'mb-option-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><h3>Initial Setup</h3></th>
        <td></td>
        </tr>
        <tr valign="top">
        <th scope="row">MenuBaron API Key</th>
        <td><input type="text" name="mb_apikey" value="<?php echo get_option('mb_apikey'); ?>" size="34" /><?php if($kv){ ?><span style="color:#C00;font-weight:bold;"> Invalid API Key!</span><?php } ?><br/>
        	<small><em>MenuBaron for WordPress is powered by the MenuBaron.com API. <?php if(!$kp||$kv){ ?><br/>A MenuBaron account is required to utilize this plugin.<?php } ?></em></small><?php if(!$kp||$kv){ ?><br/>
            <a href="http://menubaron.com/early-adopter-giveaway.php" class="button" target="_blank">Signup for MenuBaron<br/><em>FREE for limited time</em></a><?php } ?></td>
        </tr>
        <?php if($kp&&!$kv){ ?>
        <tr valign="top">
        <th scope="row"><h3>Menu Settings</h3></th>
        <td></td>
        </tr>
        <tr valign="top">
        <th scope="row">Show Prices</th>
        <td><input type="checkbox" name="mb_menu_showprices" <?php if(get_option('mb_menu_showprices')) echo 'checked="checked"'; ?> /></td>
        </tr>
        <tr valign="top">
        <th scope="row">Show Descriptions</th>
        <td><input type="checkbox" name="mb_menu_showdesc" <?php if(get_option('mb_menu_showdesc')) echo 'checked="checked"'; ?> /></td>
        </tr>
        <tr valign="top">
        <th scope="row"><h3>Widget Options</h3></th>
        <td></td>
        </tr>
        <tr valign="top">
        <th scope="row">Display Images</th>
        <td><input type="checkbox" name="mb_widget_showimage" <?php if(get_option('mb_widget_showimage')) echo 'checked="checked"'; ?> /></td>
        </tr>
        <?php } ?>
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php
}

function mb_wp_faq(){
	?>
    <style type="text/css">
		.mb_question{font-weight:bold;font-size:1.2em;display:block;}
		.mb_answer{font-style:italic;color:#222;font-size:1.1em;margin-left:16px;display:block;}
	</style>
    <h2>MenuBaron for Wordpress FAQ</h2>
    <p>&nbsp;</p>
    <p>
    	<span class="mb_question">Q: Where do I get my API Key?</span>
        <span class="mb_answer">A: Sign in to the <a href="http://kitchen.menubaron.com/" target="_blank">MenuBaron Kitchen</a> and go to the <a href="http://kitchen.menubaron.com/account.php" target="_blank">Account</a> tab.  Once there, your API Key will be displayed in the API tab.</span>
    </p>
    <p>
    	<span class="mb_question">Q: How do I display a full menu on one of my pages or posts?</span>
        <span class="mb_answer">A: Paste this shortcode anywhere you'd like within a post or page:<br/><code>[mb_fullmenu]</code></span>
    </p>
    <p>
    	<span class="mb_question">Q: How do I display my daily specials on every page of my site?</span>
        <span class="mb_answer">A: Go to <a href="widgets.php"><strong>Appearance >> Widgets</strong></a> and drag the <strong>MenuBaron - Category Widget</strong> in to the sidebar area of your choice.<br/>Set a title and select a menu category, such as "Specials".</span>
    </p>
	<?php
}


class MB_Specials_Widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		parent::__construct(
			'mb_specials_widget', // Base ID
			__('MenuBaron - Category Widget', 'text_domain'), // Name
			array( 'description' => __( 'A widget to display a single category of food such as "Today\'s Specials."', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		$category = $instance['category'];

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		
		if(get_transient('mb_menu_array')===false){
			
			$response = wp_remote_get( 'http://api.menubaron.com/utensils/menu.php?apikey='.get_option('mb_apikey') );  
			if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
				// failed to get a valid response, handle this error 
				echo 'Error Loading Widget Data';
				return;
			} 
			$d = wp_remote_retrieve_body( $response );
			set_transient('mb_menu_array',$d,300);
		}
		
		if(!get_transient('mb_menu_array')===false){
			$d = get_transient('mb_menu_array');
		}

		$j = json_decode($d, true);
		
		//print_r($j->Specials);
		foreach($j[$category] as $s){  
			echo __( '<h4  title="'.$s['Desc'].' - '.$s['Price'].'">'.$s['Name'].'</h4>', 'text_domain' );
			if(get_option('mb_widget_showimage')) echo __( '<img src="'.$s['Image'].'" title="'.$s['Desc'].' - '.$s['Price'].'" />', 'text_domain' );
		}
		 
		echo $args['after_widget'];
	}

	/**
	 * Ouputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Today\'s Specials', 'text_domain' );
		}
		if ( isset( $instance[ 'category' ] ) ) {
			$category = $instance[ 'category' ];
		}
		else {
			$category = __( 'Specials', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"><br/>
		<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Category:' ); ?></label><br/>
		<!--<input class="widefat" id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>" type="text" value="<?php echo esc_attr( $category ); ?>">-->
        <select id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>">
        <?php
		if(get_transient('mb_menu_array')===false){
			
			$response = wp_remote_get( 'http://api.menubaron.com/utensils/menu.php?apikey='.get_option('mb_apikey') );  
			if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
				// failed to get a valid response, handle this error 
				echo 'Error Loading Widget Data';
				return;
			} 
			$d = wp_remote_retrieve_body( $response );
			set_transient('mb_menu_array',$d,300);
		}
		
		if(!get_transient('mb_menu_array')===false){
			$d = get_transient('mb_menu_array');
		}

		$j = json_decode($d, true);
		foreach($j as $key=>$val){
			?>
        	<option value="<?= $key ?>" <?php if($key==esc_attr($category)) echo 'selected="selected"'; ?>><?= $key ?></option>
            <?php
		}
		?>
       	</select>
		</p>
        <p align="center"><a href="http://kitchen.menubaron.com/menu.php" class="button button-primary" target="_blank">Sign in to MenuBaron.com to manage your daily specials.</a></p>
        <p>&nbsp;</p>
		<?php 
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		// Fields
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['category'] = strip_tags($new_instance['category']);
		return $instance;
	}
}
add_action( 'widgets_init', function(){
     register_widget( 'MB_Specials_Widget' );
});



function mb_fullmenu( $atts ){
	if(get_transient('mb_menu_array')===false){
		
		$response = wp_remote_get( 'http://api.menubaron.com/utensils/menu.php?apikey='.get_option('mb_apikey') );  //TODO: Make API Key a variable
		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			// failed to get a valid response, handle this error 
			echo 'Error Loading Widget Data';
			return;
		} 
		$d = wp_remote_retrieve_body( $response );
		set_transient('mb_menu_array',$d,300);
	}
	
	if(!get_transient('mb_menu_array')===false){
		$d = get_transient('mb_menu_array');
	}
	$j = json_decode($d,true);
	//print_r($j);
	foreach($j as $cat=>$items){
		echo __('<h3>'.$cat.'</h3>');
		foreach($items as $item){
			$p=''; $desc='';
			if(get_option('mb_menu_showprices')) $p = ' - '.$item['Price'];
			if(get_option('mb_menu_showdesc')) $desc = '<br/><em>'.$item['Desc'].'</em>';
			echo __('<p class="mb_menuitem_name" data-img="'.$item['Image'].'">'.$item['Name'].$p.$desc.'</p>');
		}
	}
	?>
    <style type="text/css">
	.mb-popup{width:auto;max-width:600px;height:auto;max-height:800px;position:fixed;left:50%;top:30%;padding:10px;border:1px solid #333;-webkit-border-radius:8px;-moz-border-radius:8px;border-radius:8px;background:#fff}
	</style>
    <script type="text/javascript">
	jQuery( document ).ready(function() {
		jQuery(document).tooltip({
			items: "p, [data-image]",
			content: function(){
				var imgurl = jQuery(this).attr('data-img');
				if(imgurl!='http://images.menubaron.com/images/food/image_placeholder_lrg.jpg'){
					return '<img class="mb-popup" src="'+imgurl+'"/>';
				}
			}
		});
	});
	</script>
    <?php
}
add_shortcode( 'mb_fullmenu', 'mb_fullmenu' );

?>