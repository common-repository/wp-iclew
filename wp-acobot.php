<?php
/*
Plugin Name:  WP acobot
Plugin URI:   http://vavoomdesign.com/wordpress/scott/wp-acobot/
Description:  Add a sophisticated, customizable serivce Agent to your pages. Powered by acobot
Version:      1.4.3
Author:       Scott Campbell
Author URI:   http://vavoomdesign.com/wordpress/scott
License:      GPL v3
License URI:  https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:  wp-acobot
Domain Path: /languages/

WP acobot
Copyright (C) 2017 Scott Campbell - scott@vavoomdesign.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) or die( 'Invalid context' );

if( !defined( 'WP_ACOBOT_VER' ) )
	define( 'WP_ACOBOT_VER', '1.4.1' );

class WP_acobot {
	const OPT_KEY = 'wp_acobot_key';
	const OPT_ENB = 'wp_acobot_enb';
	const OPT_SHOW_ON_ALL = 'wp_acobot_show_on_all';
	const OPT_COLOR = 'wp_acobot_color';
	const OPT_IMG = 'wp_acobot_img';
	const ACOBOT_URL = 'https://acobot.ai/js/w?';
	const REF_KEY = '1072';
	const WP_ACOBOT_GROUP = 'wp_acobot_plugin';
	const i18n = 'wp-acobot';
    
 	static $instance = false;
  
  private function __construct() {
		// back end
		add_action	( 'admin_init', 	array( $this, 'admin_init' ) );
		add_action	( 'admin_menu', 	array( $this, 'admin_menu' ) );
		add_action	( 'plugins_loaded', 	array( $this, 'textdomain' ) );
		add_action	( 'admin_enqueue_scripts',	array( $this, 'admin_enqueue_scripts' ) );
		
		// front end
		add_shortcode	( 'run_acobot',		array( $this, 'run_acobot' ) );
		add_action	( 'wp_enqueue_scripts',	array( $this, 'enqueue_scripts' ) );
  }
  
  public static function getInstance() {
	if ( !self::$instance )
		self::$instance = new self;
	return self::$instance;
  }  
  
  public function textdomain() {
	load_plugin_textdomain( self::i18n, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }

  public function enqueue_scripts() {
		// if Enabled, Show on all Pages is true, and a key exists
		$enb = sanitize_text_field(get_option(self::OPT_ENB, '1' ));
		$showonall = sanitize_text_field(get_option(self::OPT_SHOW_ON_ALL, '1' ));
		$key = sanitize_text_field(get_option(self::OPT_KEY, '' ));
		
		if( (1 == $enb) && (1 == $showonall) && !empty($key) ) {
			$this::add_acobot_script( $key );
		}
	}
	
  private function add_acobot_script( $key ) {
		$url = self::ACOBOT_URL;

		// 2017-10-26 appears to run fine without a key, uses demo
		$params = array('key' => $key);
		if( substr( get_user_locale(), 0, 2 ) === "zh" ) {
			$params['lang'] = 'zh';
		}
		$url .= http_build_query($params);

		wp_enqueue_script( 'acobot-js', $url, array('jquery'), false, true );

		// customize the Agent
		$backcolor = sanitize_text_field(get_option(self::OPT_COLOR, '' ));
		$imgurl = sanitize_text_field(get_option(self::OPT_IMG, '' ));
		$js = $this::acobot_inline_js( $backcolor, $imgurl );
		wp_add_inline_script('acobot-js', $js);	
	}

  public function admin_enqueue_scripts( $hook ) {
		// show the assistant on the admin page
		if( $hook == 'settings_page_wp-acobot-admin-slug' ) {
			$key = '251040.R19bWEl2ztdedsXS'; // default key
			$this::add_acobot_script( $key );
		}
  }

  ///////////
	
  // We only want to load the JavaScript when the shortcode is in use.
  public function run_acobot( $atts, $content='' ) {
		// if enabled, then show the bot
		$enb = sanitize_text_field(get_option(self::OPT_ENB, '1' ));
		$showonall = sanitize_text_field(get_option(self::OPT_SHOW_ON_ALL, '0' ));
		$key = sanitize_text_field(get_option(self::OPT_KEY, '' ));

		// don't use if already  showing on all pages
		if( (1 == $enb) && (0 == $showonall) && !empty($key) ) {
			$this::add_acobot_script( $key );
		}

		// no return, we are adding JavaScript 		
		return "";
  }

	public function admin_init() {
		$group = self::WP_ACOBOT_GROUP;
		$section = 'wp_acobot_options_admin';
	  
		//if ( ! current_user_can( 'edit_page', $post_id ) ) {
		//  return $post_id;
		//}
	  
		register_setting( $group, self::OPT_KEY, 'strval' ); 
		register_setting( $group, self::OPT_ENB, 'boolean' );
		register_setting( $group, self::OPT_SHOW_ON_ALL, 'boolean' );
		register_setting( $group, self::OPT_COLOR, 'string' );
		register_setting( $group, self::OPT_IMG, 'string' );

		add_settings_section( $section, __('WP acobot Settings',self::i18n), array($this, 'setting_section_wp_acobot_callback'), $group );
  
		add_settings_field( self::OPT_KEY, __('Key',self::i18n), array($this,'callback_acobot_key'), $group, $section, array( 'label_for' => self::OPT_KEY ) );
		add_settings_field( self::OPT_ENB, __('Enabled',self::i18n), array($this,'callback_acobot_enb'), $group, $section, array( 'label_for' => self::OPT_ENB ) );
		add_settings_field( self::OPT_SHOW_ON_ALL, __('Show on all Pages',self::i18n), array($this,'callback_acobot_show_on_all'), $group, $section, array( 'label_for' => self::OPT_SHOW_ON_ALL ) );
		add_settings_field( self::OPT_COLOR, __('Color',self::i18n), array($this,'callback_acobot_color'), $group, $section, array( 'label_for' => self::OPT_COLOR ) );
		add_settings_field( self::OPT_IMG, __('Image',self::i18n), array($this,'callback_acobot_img'), $group, $section, array( 'label_for' => self::OPT_IMG ) );	  
  }
	
	public function setting_section_wp_acobot_callback( $arg ) {
		?>
		<p><?php printf( __('Please %sSign up%s to get your free group ID and key', self::i18n),'<a href="https://acobot.ai/user/register?ref=1072" target="_blank">', '</a>' ) ?>
		<br/><?php printf( __('See the official acobot %sdemo%s', self::i18n), '<a href="https://acobot.ai/demo?ref=1072" target="_blank">', '</a>' ) ?>
		</p>

		<!--<p>Your language code is <?php echo get_user_locale(); ?></p>-->

		<p><?= esc_html_e('There is also a sample Agent on this page, check the lower right of the screen', self::i18n) ?>
		<br/><?php printf( __('Ask the assistant %sWhat is acobot?%s or %sWho is on first?%s', self::i18n), '<a class="aco_question">','</a>','<a class="aco_question">','</a>' ) ?>
		</p>

		<h3>How to use</h3>
		<p><?php printf( __('Use the shortcode %s to run the Agent on your page',self::i18n), '<strong>[run_acobot/]</strong>') ?>
		</p>
		<p><?php printf( __('%sAccess your account%s to train your Agent', self::i18n), '<a href="https://acobot.ai/user?ref=' . self::REF_KEY . '" target="_blank">', '</a>' ) ?>
		</p>
    
		<?php
  
    	//<script src="https://acobot.ai/js/w"></script>
    	//<script src="https://acobot.ai/js/w?key=[key]&lang=[lang]"></script>
	}
  
  public function callback_acobot_key( $args ) {
    $setting = sanitize_text_field(get_option(self::OPT_KEY, '' ));
    ?>
	<p><input type="text" name="<?= self::OPT_KEY ?>" size="30" value="<?= isset($setting) ? esc_attr($setting) : ''; ?>">
		<br/><?= __('The acobot <strong>Key</strong> string',self::i18n) ?></p>
    <?php
  }

  public function callback_acobot_enb( $args ) {
    $setting = sanitize_text_field(get_option(self::OPT_ENB, '1' ));
    ?>
		<p><input type="checkbox" name="<?= self::OPT_ENB ?>" value="1" <? checked( '1', $setting ) ?>>
		<br/><?= __('Uncheck to turn off the Agent on your site',self::i18n) ?></p>
    <?php
	}
	
  public function callback_acobot_show_on_all( $args ) {
    $setting = sanitize_text_field(get_option(self::OPT_SHOW_ON_ALL, '0' ));
    ?>
		<p><input type="checkbox" name="<?= self::OPT_SHOW_ON_ALL ?>" value="1" <? checked( '1', $setting ) ?>>
		<br/><?= __('Check to show the agent on all page of your site',self::i18n) ?></p>
    <?php
  }
	

  public function callback_acobot_color( $args ) {
    $setting = sanitize_text_field(get_option(self::OPT_COLOR, '' ));
    ?>
		<p><input type="text" name="<?= self::OPT_COLOR ?>" size="30" value="<?= isset($setting) ? esc_attr($setting) : ''; ?>">
		<br/><?= __('The acobot background <strong>Color</strong> string',self::i18n) ?></p>
    <?php
  }

  public function callback_acobot_img( $args ) {
    $setting = sanitize_text_field(get_option(self::OPT_IMG, '' ));
    ?>
		<p><input type="text" name="<?= self::OPT_IMG ?>" size="30" value="<?= isset($setting) ? esc_attr($setting) : ''; ?>">
		<br/><?= __('Change the Agent <strong>Image</strong>',self::i18n) ?>
		<br/>Enter a valid URL</p>
    <?php
  }

  public function admin_menu() {
	add_options_page( 'WP-acobot Admin', 
		 'WP acobot', // menu name under settings
		 'manage_options', 
		 'wp-acobot-admin-slug', 
		 array($this, 'wp_acobot_admin_page') );
  }
  
  public function wp_acobot_admin_page() {
	if ( !current_user_can( 'manage_options' ) )  {
	  wp_die( __( 'You do not have sufficient permissions to access this page',self::i18n) );
	}
	
    // This needs to match the register_setting() parameter
	//$group = self::WP_ACOBOT_GROUP;
  
	echo '<div class="wrap">';
	echo '<form action="options.php" method="post">';
	settings_fields( self::WP_ACOBOT_GROUP );
	do_settings_sections( self::WP_ACOBOT_GROUP );
	submit_button( __('Save Settings',self::i18n) );
	echo '</form>';
	echo '</div>';
  }

//////////
	public function acobot_inline_js( $backcolor, $imgurl ) {
$js = <<<EOD
// override the default color and image, wait for #aco-wrapper to exist first
jQuery(document).ready(function($){
	function deferMe( backcolor, imgurl ) {
		// make sure the element exists, we may need to wait
		if( $("#aco-wrapper").length ) {

			if( !!backcolor ) {
				$("body").append("<style>.iclew-color{background-color:" + backcolor + ";}</style>");
			}
	
			// when user clicks on a question button
			$(".aco_question").click(function() {
				// Set the text of the Agent
				$("#aco-input").attr("value",$(this).text());
				// Send the data to the server (Enter Key)
				$("#aco-input").trigger( $.Event("keydown", { keyCode: 13}) );
			});

		} // restart the timer if the object does not exist yet
		else {
			setTimeout(function() { deferMe( backcolor, imgurl ) }, 50);
		}
	}
	
	deferMe("$backcolor", "$imgurl");
});
EOD;
	return $js;
	}

} // end class

// DEBUG complains if this is in the class
register_uninstall_hook( __FILE__, 'wp_acobot_uninstall_hook' );
function wp_acobot_uninstall_hook() {
	delete_option(WP_acobot::OPT_KEY);
	delete_option(WP_acobot::OPT_ENB);
	delete_option(WP_acobot::OPT_COLOR);
	delete_option(WP_acobot::OPT_IMG);	
}


// create an instance
$WP_acobot = WP_acobot::getInstance();

?>
