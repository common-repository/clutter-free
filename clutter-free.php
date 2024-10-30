<?php
/*
Plugin Name: Clutter-Free
Version: 0.4
Plugin URI: http://txfx.net/code/wordpress/clutter-free/
Description: Allows authors to hide portions of the WordPress interface that you seldom use.  Each author's preferences are stored separately and can be edited in the author's <a href="profile.php#clutter-free-options">profile</a>.  Requires WP 2.0.5 or above.
Author: Mark Jaquith
Author URI: http://txfx.net/
*/


/*
The iframe preview removal feature comes courtesy of Owen Winkler's "Kill Preview" plugin
	http://redalt.com/wiki/Kill+Preview+Plugin
The Kill Preview plugin code is Copyright 2006 Owen Winker, and licensed under the MIT License
	http://www.opensource.org/licenses/mit-license.php
*/


function txfx_clutter_free_validate($id) {
	$css_ids = array_keys(txfx_clutter_free_ids());
	if ( in_array($id, $css_ids) )
		return true;
	return false;
}


function txfx_clutter_free_ids() {
	global $txfx_clutter_free_ids;
	if ( !isset($txfx_clutter_free_ids) )
		$txfx_clutter_free_ids = array(
		'commentstatusdiv' => __('Comment Status', 'clutter-free'),
		'passworddiv' => __('Password', 'clutter-free'),
		'slugdiv' => __('Slug', 'clutter-free'),
		'categorydiv' => __('Categories', 'clutter-free'),
		'authordiv' => __('Author', 'clutter-free'),
		'poststatusdiv' => __('Post Status', 'clutter-free'),
		'posttimestampdiv' => __('Timestamp', 'clutter-free'),
		'trackbacksdiv' => __('Trackbacks', 'clutter-free'),
		'uploading' => __('Image Upload', 'clutter-free'),
		'quicktags' => __('Quicktags', 'clutter-free'),
		'postexcerpt' => __('Excerpt', 'clutter-free'),
		'postcustom' => __('Custom Fields', 'clutter-free'),
		'preview' => __('Post Preview', 'clutter-free'),
		'wp-bookmarklet' => __('Bookmarklet', 'clutter-free'),
		'footer' => __('Footer', 'clutter-free')
		);
	return (array) $txfx_clutter_free_ids;
}


function txfx_clutter_free_fetch_user_options() {
	$user = wp_get_current_user();
	$options = (array) get_user_option('txfx_clutter_free');

	// Upgrade or first-run
	$upgrade_options = array();
	foreach ( array_keys(txfx_clutter_free_ids()) as $std_option ) {
		if ( !isset($options[$std_option]) )
			$upgrade_options[$std_option] = '1';
	}

	if ( count($upgrade_options) ) {
		$options = array_merge($options, $upgrade_options);
		update_user_option($user->ID, 'txfx_clutter_free', $options);
	}

	return $options;
}


function txfx_clutter_free_css() {
	$options = txfx_clutter_free_fetch_user_options();
	$css_hidden_ids = array();
	foreach ( (array) $options as $id => $val ) {
		if ( 0 == $val && txfx_clutter_free_validate($id) )
			$css_hidden_ids[] = '#' . $id;
	}
	if ( !$css_hidden_ids ) {
		echo '<!-- ' . __('Clutter Free plugin: no GUI elements are being hidden', 'clutter-free') . ' -->';
		return;
	}
	$css_id_string = implode(', ', $css_hidden_ids);
	echo "\n<!-- " . __('Clutter Free plugin CSS:', 'clutter-free') . " -->\n<style type='text/css'>\n<!--\n$css_id_string { display: none !important; }\n-->\n</style>\n";
}


function txfx_clutter_free_kill_iframes_init() {
	if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin/post.php') === false )
		return;
	$options = txfx_clutter_free_fetch_user_options();
	if ( isset($options['preview']) && $options['preview'] == '0' )
		ob_start('txfx_clutter_free_kill_preview');
	if ( isset($options['uploading']) && $options['uploading'] == '0' )
		ob_start('txfx_clutter_free_kill_uploading');
}


function txfx_clutter_free_kill_preview($content) {
	global $post;
	
	$content = preg_replace("/<div[^>]*?id=['\"]preview['\"].*?<\/div>/mis", '', $content);
	$content = preg_replace('/<a href="#preview-post">/mis', '<a href="' . add_query_arg('preview', 'true', get_permalink($post->ID)) . '" onclick="this.target=\'_blank\';">', $content);
	return $content;
}


function txfx_clutter_free_kill_uploading($content) {
	return preg_replace("/<iframe[^>]*?id=['\"]uploading['\"].*?<\/iframe>/mis", '', $content);
}


function txfx_clutter_free_options() {
		$options = (array) txfx_clutter_free_fetch_user_options();
		$css_ids = (array) txfx_clutter_free_ids();
	?>
	<p id="clutter-free-options"><?php _e('Display the following elements on the post screen:', 'clutter-free'); ?></p>
	<ul>
	<?php foreach ( $css_ids as $css_id => $css_id_name ) { ?>
		<li><label for="txfx_clutter_free_<?php echo $css_id; ?>"><input name="txfx_clutter_free[<?php echo $css_id; ?>]" id="txfx_clutter_free_<?php echo $css_id; ?>" type="checkbox" value="1" <?php checked('1', $options[$css_id]); ?> />
			<?php echo $css_id_name; ?></label></li>
	<?php } ?>
	</ul>
	<?php
}


function txfx_clutter_free_update() {
	$user = wp_get_current_user();
	$updated_css_ids = array();
	$css_ids = array_keys(txfx_clutter_free_ids());
	foreach ( $css_ids as $css_id )
		$updated_css_ids[$css_id] = ( $_POST['txfx_clutter_free'][$css_id] ) ? '1' : '0';
	update_user_option($user->ID, 'txfx_clutter_free', $updated_css_ids);
}


add_action('admin_head', 'txfx_clutter_free_css');
add_action('profile_personal_options', 'txfx_clutter_free_options');
add_action('personal_options_update', 'txfx_clutter_free_update');
add_action('init', create_function('$a=0','load_plugin_textdomain("clutter-free");'), 10);
add_action('init', 'txfx_clutter_free_kill_iframes_init', 11);
?>