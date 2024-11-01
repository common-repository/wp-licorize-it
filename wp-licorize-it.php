<?php
/*
Plugin Name: Licorize for Wordpress
Plugin URI: http://licorize.com
Description: Adds a button which allows you to add bookmark in Licorize.com
Version: 0.3
Author: Open Lab
Author URI: http://licorize.com
*/

// compatibile with WordPress 2.6 and older
// see http://codex.wordpress.org/Determining_Plugin_and_Content_Directories
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
if ( ! defined( 'WPMU_PLUGIN_URL' ) )
      define( 'WPMU_PLUGIN_URL', WP_CONTENT_URL. '/mu-plugins' );
if ( ! defined( 'WPMU_PLUGIN_DIR' ) )
      define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );

if( !isset($LIC_locale) )
	$LIC_locale = '';
	
$LIC_IT_plugin_basename = plugin_basename(dirname(__FILE__));
$LIC_IT_plugin_url_path = ""; // /wp-content/plugins/wp-licorize-it
if ( !defined('WP_CONTENT_URL') ) {
	$LIC_IT_plugin_url_path = get_option('siteurl').'/wp-content/plugins/'.$LIC_IT_plugin_basename.'/';
} else {
	$LIC_IT_plugin_url_path = WP_CONTENT_URL.'/plugins/'.$LIC_IT_plugin_basename.'/';
}

// Fix SSL
if (function_exists('is_ssl') && is_ssl()) // @since 2.6.0
	$LIC_IT_plugin_url_path = str_replace('http:', 'https:', $LIC_IT_plugin_url_path);


add_action( 'wp_print_scripts', 'licorize_it_scripts' );
add_action( 'wp_print_styles', 'licorize_it_styles' );

function licorize_it_scripts() {
	wp_register_script('com.licorize.wp_plugin.script', plugins_url('wp-licorize-it/wp-licorize-it.js'), array( 'jquery' ), '1.0'); 
   	wp_enqueue_script('com.licorize.wp_plugin.script');
}

function licorize_it_styles() {
	wp_register_style('com.licorize.wp_plugin.style', WP_PLUGIN_URL . '/wp-licorize-it/wp-licorize-it.css');
	wp_enqueue_style( 'com.licorize.wp_plugin.style' );
}
	
function licorize_init() {
	global $LIC_IT_plugin_url_path, $LIC_IT_plugin_basename;
	
	load_plugin_textdomain('wp-licorize-it',
		$LIC_IT_plugin_url_path.'/languages',
		$LIC_IT_plugin_basename.'/languages');
}
add_filter('init', 'licorize_init');

function LIC_IT_KIT( $args = false ) {
	
	if ( ! isset($args['html_container_open']))
		$args['html_container_open'] = "<ul class=\"licorize_it_list\">";
	if ( ! isset($args['html_container_close']))
		$args['html_container_close'] = "</ul>";
				
	if ( ! isset($args['html_wrap_open']))
		$args['html_wrap_open'] = "<li>";
	if ( ! isset($args['html_wrap_close']))
		$args['html_wrap_close'] = "</li>";
	
    $licoSrc = WP_PLUGIN_URL.'/wp-licorize-it/icons/'.get_option('licorize_button_size').'.png';
    if('' == get_option('licorize_button_size')) {
	    $licoSrc = WP_PLUGIN_URL.'/wp-licorize-it/icons/lic22x86.png';
    }
    
    $lico_action = get_option('licorize_action');
    if("" == $lico_action)
    	$lico_action = "REMIND";

	$lico_url = get_permalink($post->ID);
    $lico_title = get_the_title($post->ID);

	$lico_keywords = "";
	foreach( wp_get_post_tags( $post->ID ) as $tag) {
		$lico_keywords = $lico_keywords . ", " . $tag->name;
	}

    $licoCode = "com.licorize.wp_plugin.licorize_it('$lico_action', '$lico_url', '$lico_title', '$lico_keywords')";

    $kit_html = "<img style=\"cursor:pointer;\" alt=\"Licorize\" src=\"$licoSrc\" title=\"Licorize\" onclick=\"$licoCode\" />";
	
	if($args['output_later'])
		return $kit_html;
	else
		echo $kit_html;
}

function LIC_IT_to_bottom_of_content($content) {
	global $LIC_IT_auto_placement_ready;
	$is_feed = is_feed();
	
	/*if( ! $LIC_IT_auto_placement_ready)
		return $content;
	*/
	if ( 
		( 
			// Tags
			// <!--sharesave--> tag
			strpos($content, '<!--sharesave-->')===false || 
			// <!--nosharesave--> tag
			strpos($content, '<!--nosharesave-->')!==false
		) && (
			( (strpos($content, '<!--nosharesave-->')!==false) )		
		)
	)	
		return $content;
	
	
	$kit_args = array(
		"output_later" => true,
		"html_container_open" => ($is_feed) ? "" : "<ul class=\"licorize_it_list\">",
		"html_container_close" => ($is_feed) ? "" : "</ul>",
		"html_wrap_open" => ($is_feed) ? "" : "<li>",
		"html_wrap_close" => ($is_feed) ? " " : "</li>",
	);
	
	if ( ! $is_feed ) {
		$container_wrap_open = '<div class="lic_it_container">';
		$container_wrap_close = '</div>';
	} else {
		$container_wrap_open = '<p>';
		$container_wrap_close = '</p>';
	}
	
	$content .= $container_wrap_open.LIC_IT_KIT($kit_args).$container_wrap_close;
	return $content;
}

// Only automatically output button code after the_title has been called - to avoid premature calling from misc. the_content filters (especially meta description)
add_filter('the_content', 'LIC_IT_to_bottom_of_content', 98);

function LIC_IT_init() {
	if(function_exists('register_setting')) {
        register_setting('licorize-options', 'licorize_action');
        register_setting('licorize-options', 'licorize_button_size');
    }
}

function LIC_IT_options() {
	add_menu_page('Licorize', 'Licorize', 8, basename(__FILE__), 'LIC_IT_options_page');
}

function LIC_IT_activate(){
    add_option('licorize_action', 'REMIND');
    add_option('licorize_button_size', 'NORMAL');
}

if(is_admin()) {
    add_action('admin_menu', 'LIC_IT_options');
    add_action('admin_init', 'LIC_IT_init');
}

register_activation_hook( __FILE__, 'LIC_IT_activate');

function LIC_IT_options_page() {

?>

		 <div class="wrap" style="font-size:13px;">

			<div class="icon32" id="icon-options-general"><br/></div><h2>Settings for Licorize Button</h2>

			<div id="licorize_canvas" style="width:800px;float:left">

			<p>This plugin will add a "Add to Licorize" button to each blog post in both the contents of your post and the RSS feed.</p>

			<form method="post" action="options.php">

			<?php

				// New way of setting the fields, for WP 2.7 and newer

				if(function_exists('settings_fields')) {

					settings_fields('licorize-options');
				}
		    ?>

	<table  class="form-table">

            <tr>

                <th scope="row">Type</th>
                <td>
                    <p>
                    <input type="radio" value="BOOKMARK" <?php if (get_option('licorize_action') == 'BOOKMARK') echo 'checked="checked"'; ?> name="licorize_action" id="licorize_action_BOOKMARK" group="licorize_action" />

                    <label for="licorize_action_BOOKMARK">Add bookmark</label>

                    </p>

                    <p>
                     <input type="radio" value="REMIND" <?php if (get_option('licorize_action') == 'REMIND') echo 'checked="checked"'; ?> name="licorize_action" id="licorize_action_REMIND" group="licorize_action" />

                     <label for="licorize_action_REMIND">Remind me later</label>

                    </p>

                </td>

            </tr>

            <tr>

                <th scope="row">Style</th>
                <td>
                    <p>
                    <input type="radio" value="lic_20x22b" <?php if (get_option('licorize_button_size') == 'lic_20x22b') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_20x22b" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_20x22b">lic_20x22b</label>
					<img id="licorize_style_preview_lic_20x22b" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_20x22b.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_20x22c" <?php if (get_option('licorize_button_size') == 'lic_20x22c') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_20x22c" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_20x22c">lic_20x22c</label>
					<img id="licorize_style_preview_lic_20x22c" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_20x22c.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_20x86b" <?php if (get_option('licorize_button_size') == 'lic_20x86b') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_20x86b" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_20x86b">lic_20x86b</label>
					<img id="licorize_style_preview_lic_20x86b" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_20x86b.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_20x86c" <?php if (get_option('licorize_button_size') == 'lic_20x86c') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_20x86c" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_20x86c">lic_20x86c</label>
					<img id="licorize_style_preview_lic_20x86c" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_20x86c.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_22x22" <?php if (get_option('licorize_button_size') == 'lic_22x22') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_22x22" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_22x22">lic_22x22</label>
					<img id="licorize_style_preview_lic_22x22" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_22x22.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_22x86" <?php if (get_option('licorize_button_size') == 'lic_22x86') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_22x86" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_22x86">lic_22x86</label>
					<img id="licorize_style_preview_lic_22x86" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_22x86.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_36x153a" <?php if (get_option('licorize_button_size') == 'lic_36x153a') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_36x153a" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_36x153a">lic_36x153a</label>
					<img id="licorize_style_preview_lic_36x153a" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_36x153a.png" />
                    </p>

                    <p>
                    <input type="radio" value="lic_36x153b" <?php if (get_option('licorize_button_size') == 'lic_36x153b') echo 'checked="checked"'; ?> name="licorize_button_size" id="licorize_button_size_lic_36x153b" group="licorize_button_size" />
                    <label for="licorize_button_size_lic_36x153b">lic_36x153b</label>
					<img id="licorize_style_preview_lic_36x153b" src="<?php echo WP_PLUGIN_URL ?>/wp-licorize-it/icons/lic_36x153b.png" />
                    </p>
                </td>

            </tr>

        </table>

        <p class="submit">

            <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />

        </p>

    </form>

		</div> <!--End of licorize_canvas-->

		</div><br /><br />

		</div>

    </div>
<?php
}