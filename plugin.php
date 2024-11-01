<?php 
/*
 Plugin Name: TS Comfort Database
 Plugin URI: https://www.spiess-informatik.de/wordpress-plugins/ts-comfort-database/
 License: GPLv3 (license.txt)
 Description: Database Explorer for WordPress
 Author: Tobias Spiess
 Author URI: https://www.spiess-informatik.de
 Version: 2.0.7
 Text-Domain: tsinf_comfortdb_plugin_textdomain
 Domain Path: /languages
 */

/**
TS Comfort Database - A Database Explorer for Wordpress
Copyright (C) 2017 Tobias Spiess

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

define('TS_INF_COMFORT_DB_PATH', dirname(__FILE__));
define('TS_INF_COMFORT_DB_MAIN_FILE', dirname(__FILE__) . '/plugin.php');

if(!function_exists('get_home_path'))
{
    require_once(ABSPATH . 'wp-admin/includes/file.php');
}

define('TS_INF_COMFORT_DB_UPLOAD_DIR', sprintf("%swp-content/ts-plugins/ts-comfort-database/", get_home_path()));
define('TS_INF_TABLE_TO_CSV_NONCE', 'hsdfdh#op3403f-dGJkl345');


/**
 * Load Plugin Translations
 */
function tsinf_comfortdb_load_plugin_textdomain() {
	load_plugin_textdomain( 'tsinf_comfortdb_plugin_textdomain', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'tsinf_comfortdb_load_plugin_textdomain' );

/**
* Set TS Comfort Database Warning Message
*/
function tsinf_comfortdb_render_start_warning_admin_notice() { 
    $message_already_confirmed = (int) get_option('tsinf_comfortdb_plugin_confirm_risk_message');
    if($message_already_confirmed !== 1)
    {
?>
	<div class="notice notice-error">
		<p><strong><?php _e('TS Comfort DB Message: Please be careful by using this plugin. If you work on your wordpress database you work on the open heart of wordpress. Be sure, you know what you do! Make a backup of your database and ensure that the created backup is working, before working on the database with this plugin!', 'tsinf_comfortdb_load_plugin_textdomain'); ?></strong><br /><button id="tsinf_comfortdb_risk_message_button" data-riskconfirm-auth="<?php echo wp_create_nonce('tsinf_comfortdb_plugin_confirm_risk_message'); ?>"><?php _e('I know the risk. Do not show this message again', 'tsinf_comfortdb_load_plugin_textdomain'); ?></button></p>
	</div>
	
<?php 
    }
}
add_action('admin_notices', 'tsinf_comfortdb_render_start_warning_admin_notice');


include dirname( __FILE__ ) . '/classes/columnmeta.class.php';
include dirname( __FILE__ ) . '/classes/database.class.php';
include dirname( __FILE__ ) . '/classes/filemanager.class.php';
include dirname( __FILE__ ) . '/classes/tsinf_comfort_db.class.php';
include dirname( __FILE__ ) . '/classes/tsinf_comfort_post_search.php';
include dirname( __FILE__ ) . '/classes/tsinf_comfort_db_exporter.php';


$ts_inf_comfort_db = new TS_INF_COMFORT_DB();
?>