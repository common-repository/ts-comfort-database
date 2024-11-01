<?php
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


/**
 * Plugin Class for Global Post Search
 * @author Tobias Spiess
 */

if(!class_exists('TS_INF_GLOBAL_POST_SEARCH'))
{
    class TS_INF_GLOBAL_POST_SEARCH {
        private static $skin;
        private static $valid_skins;
        
        function __construct()
        {
            $this->initialize();
        }
        
        /**
         * Initialize Components
         */
        private function initialize()
        {
            $skin = get_option('tscdb_skin');
            self::$valid_skins = array("wordpress", "tscdb");
            self::$skin = (is_string($skin) && strlen($skin) > 0 && in_array($skin, self::$valid_skins)) ? $skin : "tscdb";
            
             add_action('admin_menu', array(__CLASS__, 'register_post_search_page'), 999);
             add_action('admin_enqueue_scripts', array(__CLASS__, 'load_backend_scripts'));
             add_action('wp_ajax_tsinf_ps_get_data', array(__CLASS__, 'get_results_by_ajax'));
        }
        
        /**
         * Load Backend Scripts
         */
        public static function load_backend_scripts()
        {
            wp_enqueue_style('tsinf_comfort_db_global_search_css', plugins_url('../css/global_search.css', __FILE__));
            wp_enqueue_script('tsinf_comfort_db_global_search_css', plugins_url('../js/global_search.js', __FILE__), array('jquery'));
        }
        
        /**
         * Register Post Search Page
         */
        public static function register_post_search_page()
        {
            add_submenu_page(
                'tscomfortdb-mainpage',
                __('Global Post Search', 'tsinf_comfortdb_plugin_textdomain'),
                __('Global Post Search', 'tsinf_comfortdb_plugin_textdomain'),
                'manage_options',
                'tsinf_comfortdb_plugin_post_search',
                array('TS_INF_GLOBAL_POST_SEARCH', 'render_post_search')
                );
        }
        
        /**
         * Evaluate Search Parameters, Query with Parameters and return Results
         * @param array $post Search-Parameter
         * @param int $offset Search-Results Offset 
         * @param int $limit Search-Results Limit
         * @return array Search Results
         */
        private static function get_search_results($post, $offset = 0, $limit = 10)
        {
            global $wpdb;
            
            $offset = (int) $offset;
            $limit = (int) $limit;
            
            $search_string = sanitize_text_field($post['ts_comfortdb_global_post_search']['search_string']);
            
            $search_in_post_type = array();
            if(is_array($post['ts_comfortdb_global_post_search']['post_types']) && count($post['ts_comfortdb_global_post_search']['post_types']) > 0)
            {
                foreach($post['ts_comfortdb_global_post_search']['post_types'] as $post_type)
                {
                    $search_in_post_type[] = "'" . sanitize_text_field($post_type) . "'";
                }
            }
            
            $search_in_post_type_str = implode(",", $search_in_post_type);
            
            $search_in_fields_str = "";
            if(strlen($search_string) > 0)
            {
                $search_in_fields = array();
                if(is_array($post['ts_comfortdb_global_post_search']['search_for']) && count($post['ts_comfortdb_global_post_search']['search_for']) > 0)
                {
                    foreach($post['ts_comfortdb_global_post_search']['search_for'] as $search_for)
                    {
                        $search_in_fields[] = sanitize_text_field($search_for) . " LIKE '%" . $search_string . "%'";
                    }
                }
                $search_in_fields_str = " AND (" . implode(" OR ", $search_in_fields) . ")";
            }
            
            $search_in_post_status = array();
            if(is_array($post['ts_comfortdb_global_post_search']['post_status']) && count($post['ts_comfortdb_global_post_search']['post_status']) > 0)
            {
                foreach($post['ts_comfortdb_global_post_search']['post_status'] as $post_status)
                {
                    $search_in_post_status[] = "'" . sanitize_text_field($post_status) . "'";
                }
            }
            
            $search_in_post_status_str = implode(",", $search_in_post_status);
            
            
            $order_by_1 = sanitize_text_field($post['ts_comfortdb_global_post_search']['order_by'][1]);
            $order_by_2 = sanitize_text_field($post['ts_comfortdb_global_post_search']['order_by'][2]);
            $order_by_3 = sanitize_text_field($post['ts_comfortdb_global_post_search']['order_by'][3]);
            
            $order_1 = sanitize_text_field($post['ts_comfortdb_global_post_search']['order'][1]);
            $order_2 = sanitize_text_field($post['ts_comfortdb_global_post_search']['order'][2]);
            $order_3 = sanitize_text_field($post['ts_comfortdb_global_post_search']['order'][3]);
            
            $order_by = array();
            
            if(strlen($order_by_1) > 0 && $order_by_1 !== -1 && is_string($order_by_1))
            {
                if($order_1 === 'desc')
                {
                    $order_by[] = $order_by_1 . " DESC ";
                } else {
                    $order_by[] = $order_by_1 . " ASC";
                }
            }
            
            if(strlen($order_by_2) > 0 && $order_by_2 !== -1 && is_string($order_by_2))
            {
                if($order_by_2 === 'desc')
                {
                    $order_by[] = $order_by_2 . " DESC ";
                } else {
                    $order_by[] = $order_by_2 . " ASC";
                }
            }
            
            if(strlen($order_by_3) > 0 && $order_by_3 !== -1 && is_string($order_by_3))
            {
                if($order_by_3 === 'desc')
                {
                    $order_by[] = $order_by_3 . " DESC ";
                } else {
                    $order_by[] = $order_by_3 . " ASC";
                }
            }
            
            $order_by_str = "";
            if(is_array($order_by) && count($order_by) > 0)
            {
                $order_by_str = " ORDER BY " . implode(",", $order_by);
            }
            
            
            $sql = sprintf("SELECT p.ID as post_id
						FROM %sposts p
						LEFT JOIN %spostmeta pm ON p.ID = pm.post_id
						WHERE p.post_type IN (%s)
						%s
						AND p.post_status IN (%s)
						GROUP BY p.ID
						%s
                        LIMIT %d OFFSET %d;", $wpdb->prefix, $wpdb->prefix, $search_in_post_type_str, $search_in_fields_str, $search_in_post_status_str, $order_by_str, $limit, $offset);
            
            $search_results = $wpdb->get_results($sql);
            
            return $search_results;
        }
        
        /**
         * AJAX-Callback to get more Results
         */
        public static function get_results_by_ajax()
        {
            if(isset($_POST['action']) && $_POST['action'] === 'tsinf_ps_get_data' && isset($_POST['rq']) && isset($_POST['offset'])) 
            {
                $rq = $_POST['rq'];
                
                $post = array();
                try {
                    parse_str($rq, $post);
                } catch (Exception $e)
                {
                    $post = array();
                }
                
                
                $offset = (int) $_POST['offset'];
                
                check_ajax_referer('tsinf_comfortdb_plugin_post_search', 'security', true);
                
                $search_result = self::get_search_results($post, $offset);
                
                $post_data = array();
                if(is_array($search_result) && count($search_result) > 0)
                {
                    foreach($search_result as $sresult)
                    {
                        $post_data[] = get_post($sresult->post_id);
                    }
                }
                
                wp_send_json($post_data);
            }
                
            wp_die();
        }
        
        /**
         * Render Post Search Form and Result-Table
         */
        public static function render_post_search() {
            if ( !current_user_can( 'edit_posts' ) )  {
                wp_die( __('You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain'));
            }
            
            global $wpdb;
            $search_results = array();
            if(isset($_POST['ts_comfortdb_global_post_search']))
            {
                $search_results = self::get_search_results($_POST);                
            }
            
            $tsinf_ps_nonce = wp_create_nonce('tsinf_comfortdb_plugin_post_search');
            
            ?>
			<h1><?php _e('TS Comfort Database: Post Search', 'tsinf_comfortdb_plugin_textdomain'); ?></h1>
			
			<form method="POST" action="" id="tsinf_comfort_db_global_search_form" data-auth="<?php echo $tsinf_ps_nonce; ?>" data-admin-url="<?php echo admin_url("post.php?post=###ID###&action=edit"); ?>">
				<input type="text" placeholder="<?php _e('Enter Searchword here', 'tsinf_comfortdb_plugin_textdomain'); ?>" name="ts_comfortdb_global_post_search[search_string]" id="ts_comfortdb_global_post_search_string" class="ts_comfortdb_global_post_search search_string tsinf_comfortdb_full_text_search" value="<?php if(isset($_POST['ts_comfortdb_global_post_search']['search_string'])) { echo $_POST['ts_comfortdb_global_post_search']['search_string']; }?>" />
				<input type="submit" name="ts_comfortdb_global_post_search[submit_button]" id="ts_comfortdb_global_post_search_submit" class="tsinf_symbol_button ts_comfortdb_global_post_search submit_button" value="<?php _e('Search', 'tsinf_comfortdb_plugin_textdomain'); ?>" />
				<div id="tssesl_gps_filter_container">
				<?php 
				$args = array(
					'public'   => true,
					'show_ui' => true
				);
				$post_types = get_post_types($args, 'object');
				if(is_array($post_types) && count($post_types) > 0)
				{
					?>
					<div id="tssesl_gps_filter_box_post_types" class="tssesl_gps_filter_box">
						<p class="filter_box_headline"><strong><?php _e('Search in Post Types', 'tsinf_comfortdb_plugin_textdomain'); ?>:</strong></p><?php 
					foreach($post_types as $post_type)
					{
						$checked = "";
						if(
								isset($_POST['ts_comfortdb_global_post_search']['post_types']) && 
								is_array($_POST['ts_comfortdb_global_post_search']['post_types']) && 
								in_array($post_type->name, $_POST['ts_comfortdb_global_post_search']['post_types']))
						{
							$checked = "checked='checked'";
						} else if(isset($_POST['ts_comfortdb_global_post_search']) === false)
						{
							$checked = " checked='checked' ";
						}
						?>
						<span class="tsinf_search_slug_line">
							<input type="checkbox" name="ts_comfortdb_global_post_search[post_types][]" <?php echo $checked; ?> value="<?php echo $post_type->name; ?>" />
							<label for="ts_comfortdb_global_post_search[post_types][]" class="ts_comfortdb_global_post_search_label label <?php echo $post_type->name; ?>"><?php echo $post_type->label; ?> (<?php echo $post_type->name; ?>)</label>
						</span>
						<?php
					}
					?>
					</div>
					<?php 
				}
				
				$post_status_items = $wpdb->get_results("SELECT post_status FROM " . $wpdb->prefix . "posts GROUP BY post_status ORDER BY post_status;");
				if(is_array($post_status_items) && count($post_status_items) > 0)
				{
					?>
					<div id="tssesl_gps_filter_box_post_status" class="tssesl_gps_filter_box">
					<p class="filter_box_headline"><strong><?php _e('Search in Posts with Status', 'tsinf_comfortdb_plugin_textdomain'); ?>:</strong></p><?php 
					foreach($post_status_items as $post_status)
					{
						$checked = "";
						if(
								isset($_POST['ts_comfortdb_global_post_search']['post_status']) && 
								is_array($_POST['ts_comfortdb_global_post_search']['post_status']) && 
								in_array($post_status->post_status, $_POST['ts_comfortdb_global_post_search']['post_status']))
						{
							$checked = "checked='checked'";
						} else if(isset($_POST['ts_comfortdb_global_post_search']['post_status']) === false && ($post_status->post_status === 'private' || $post_status->post_status === 'publish'))
						{
							$checked = "checked='checked'";
						}
						?>
						<span class="tsinf_search_slug_line">
							<input type="checkbox" name="ts_comfortdb_global_post_search[post_status][]" <?php echo $checked; ?> value="<?php echo $post_status->post_status; ?>" />
							<label for="ts_comfortdb_global_post_search[post_status][]" class="ts_comfortdb_global_post_search_label label <?php echo $post_status->post_status; ?>"><?php echo $post_status->post_status; ?></label>
						</span>
						<?php
					}
					?>
					</div>
					<?php 
				}

				?>
				<div id="tssesl_gps_filter_box_post_fields" class="tssesl_gps_filter_box">
					<p class="filter_box_headline"><strong><?php _e('Search in Post Fields', 'tsinf_comfortdb_plugin_textdomain'); ?>:</strong></p>
					<span class="tsinf_search_slug_line">
						<input type="checkbox" name="ts_comfortdb_global_post_search[search_for][]" <?php if((isset($_POST['ts_comfortdb_global_post_search']) && isset($_POST['ts_comfortdb_global_post_search']['search_for']) && in_array('p.ID', $_POST['ts_comfortdb_global_post_search']['search_for'])) || (!isset($_POST['ts_comfortdb_global_post_search']))) { echo "checked='checked'"; }; ?> value="p.ID" />
						<label for="ts_comfortdb_global_post_search[search_for][]" class="ts_comfortdb_global_post_search_label label search_for_id"><?php _e('Post-ID', 'tsinf_comfortdb_plugin_textdomain'); ?></label>
					</span>
					<span class="tsinf_search_slug_line">
						<input type="checkbox" name="ts_comfortdb_global_post_search[search_for][]" <?php if((isset($_POST['ts_comfortdb_global_post_search']) && isset($_POST['ts_comfortdb_global_post_search']['search_for']) && in_array('p.post_title', $_POST['ts_comfortdb_global_post_search']['search_for'])) || (!isset($_POST['ts_comfortdb_global_post_search']))) { echo "checked='checked'"; }; ?> value="p.post_title" />
						<label for="ts_comfortdb_global_post_search[search_for][]" class="ts_comfortdb_global_post_search_label label search_for_post_title"><?php _e('Post-Title', 'tsinf_comfortdb_plugin_textdomain'); ?></label>
					</span>
					<span class="tsinf_search_slug_line">
						<input type="checkbox" name="ts_comfortdb_global_post_search[search_for][]" <?php if((isset($_POST['ts_comfortdb_global_post_search']) && isset($_POST['ts_comfortdb_global_post_search']['search_for']) && in_array('p.post_content', $_POST['ts_comfortdb_global_post_search']['search_for'])) || (!isset($_POST['ts_comfortdb_global_post_search']))) { echo "checked='checked'"; }; ?> value="p.post_content" />
						<label for="ts_comfortdb_global_post_search[search_for][]" class="ts_comfortdb_global_post_search_label label search_for_post_content"><?php _e('Post-Content', 'tsinf_comfortdb_plugin_textdomain'); ?></label>
					</span>
					<span class="tsinf_search_slug_line">
						<input type="checkbox" name="ts_comfortdb_global_post_search[search_for][]" <?php if((isset($_POST['ts_comfortdb_global_post_search']) && isset($_POST['ts_comfortdb_global_post_search']['search_for']) && in_array('pm.meta_key', $_POST['ts_comfortdb_global_post_search']['search_for'])) || (!isset($_POST['ts_comfortdb_global_post_search']))) { echo "checked='checked'"; }; ?> value="pm.meta_key" />
						<label for="ts_comfortdb_global_post_search[search_for][]" class="ts_comfortdb_global_post_search_label label search_for_meta_key"><?php _e('Post-Meta Key', 'tsinf_comfortdb_plugin_textdomain'); ?></label>
					</span>
					<span class="tsinf_search_slug_line">
						<input type="checkbox" name="ts_comfortdb_global_post_search[search_for][]" <?php if((isset($_POST['ts_comfortdb_global_post_search']) && isset($_POST['ts_comfortdb_global_post_search']['search_for']) && in_array('pm.meta_value', $_POST['ts_comfortdb_global_post_search']['search_for'])) || (!isset($_POST['ts_comfortdb_global_post_search']))) { echo "checked='checked'"; }; ?> value="pm.meta_value" />
						<label for="ts_comfortdb_global_post_search[search_for][]" class="ts_comfortdb_global_post_search_label label search_for_meta_value"><?php _e('Post-Meta Value', 'tsinf_comfortdb_plugin_textdomain'); ?></label>
					</span>
				</div>
				
				<div id="tssesl_gps_filter_box_post_fields" class="tssesl_gps_filter_box">
					<p class="filter_box_headline"><strong><?php _e('Sort Results', 'tsinf_comfortdb_plugin_textdomain'); ?>:</strong></p>
					<span class="tsinf_search_slug_line">
						<select name="ts_comfortdb_global_post_search[order_by][1]" id="ts_comfortdb_global_post_search_order_by_1" class="ts_comfortdb_global_post_search_order_by_1">
							<option value="-1"><?php _e('--- Select Field ---', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.ID" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][1]) && $_POST['ts_comfortdb_global_post_search']['order_by'][1] === 'p.ID') { echo "selected='selected'"; }; ?>><?php _e('Post-ID', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.post_title" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][1]) && $_POST['ts_comfortdb_global_post_search']['order_by'][1] === 'p.post_title') { echo "selected='selected'"; }; ?>><?php _e('Post-Title', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.post_content" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][1]) && $_POST['ts_comfortdb_global_post_search']['order_by'][1] === 'p.post_content') { echo "selected='selected'"; }; ?>><?php _e('Post-Content', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
						
						<select name="ts_comfortdb_global_post_search[order][1]" id="ts_comfortdb_global_post_search_order_1" class="ts_comfortdb_global_post_search_order_1">
							<option value="asc" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order'][1]) && $_POST['ts_comfortdb_global_post_search']['order'][1] === 'asc') { echo "selected='selected'"; }; ?>><?php _e('ascending', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="desc" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order'][1]) && $_POST['ts_comfortdb_global_post_search']['order'][1] === 'desc') { echo "selected='selected'"; }; ?>><?php _e('descending', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
					</span>
					<span class="tsinf_search_slug_line">
						<select name="ts_comfortdb_global_post_search[order_by][2]" id="ts_comfortdb_global_post_search_order_by_2" class="ts_comfortdb_global_post_search_order_by_2">
							<option value="-1"><?php _e('--- Select Field ---', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.ID" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][1]) && $_POST['ts_comfortdb_global_post_search']['order_by'][2] === 'p.ID') { echo "selected='selected'"; }; ?>><?php _e('Post-ID', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.post_title" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][1]) && $_POST['ts_comfortdb_global_post_search']['order_by'][2] === 'p.post_title') { echo "selected='selected'"; }; ?>><?php _e('Post-Title', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.post_content" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][1]) && $_POST['ts_comfortdb_global_post_search']['order_by'][2] === 'p.post_content') { echo "selected='selected'"; }; ?>><?php _e('Post-Content', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
						
						<select name="ts_comfortdb_global_post_search[order][2]" id="ts_comfortdb_global_post_search_order_2" class="ts_comfortdb_global_post_search_order_2">
							<option value="asc" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order'][2]) && $_POST['ts_comfortdb_global_post_search']['order'][2] === 'asc') { echo "selected='selected'"; }; ?>><?php _e('ascending', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="desc" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order'][2]) && $_POST['ts_comfortdb_global_post_search']['order'][2] === 'desc') { echo "selected='selected'"; }; ?>><?php _e('descending', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
					</span>
					<span class="tsinf_search_slug_line">
						<select name="ts_comfortdb_global_post_search[order_by][3]" id="ts_comfortdb_global_post_search_order_by_3" class="ts_comfortdb_global_post_search_order_by_3">
							<option value="-1"><?php _e('--- Select Field ---', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.ID" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][3]) && $_POST['ts_comfortdb_global_post_search']['order_by'][3] === 'p.ID') { echo "selected='selected'"; }; ?>><?php _e('Post-ID', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.post_title" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][3]) && $_POST['ts_comfortdb_global_post_search']['order_by'][3] === 'p.post_title') { echo "selected='selected'"; }; ?>><?php _e('Post-Title', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="p.post_content" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order_by'][3]) && $_POST['ts_comfortdb_global_post_search']['order_by'][3] === 'p.post_content') { echo "selected='selected'"; }; ?>><?php _e('Post-Content', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
						
						<select name="ts_comfortdb_global_post_search[order][3]" id="ts_comfortdb_global_post_search_order_3" class="ts_comfortdb_global_post_search_order_3">
							<option value="asc" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order'][3]) && $_POST['ts_comfortdb_global_post_search']['order'][3] === 'asc') { echo "selected='selected'"; }; ?>><?php _e('ascending', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="desc" <?php if(isset($_POST['ts_comfortdb_global_post_search']['order'][3]) && $_POST['ts_comfortdb_global_post_search']['order'][3] === 'desc') { echo "selected='selected'"; }; ?>><?php _e('descending', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
					</span>
				</div>
			</div>
				
			</form>
			
			<div id="ts_comfortdb_global_post_search_results_main" class="tsinf_comfortdb_table_data_wrapper">
			<?php 
			if(is_array($search_results) && count($search_results) > 0)
			{
				?>
				<table class="tsinf_comfortdb_table <?php echo (self::$skin === "wordpress") ? "wp-list-table widefat striped table-view-list" : ""; ?>">
					<thead>
    					<tr>
    						<th class="tsinf_comfortdb_column_headline col_data_id"><?php _e('ID', 'tsinf_comfortdb_plugin_textdomain'); ?></<th>
    						<th class="tsinf_comfortdb_column_headline col_data_author"><?php _e('Author', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_title"><?php _e('Title', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_status"><?php _e('Status', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_postname"><?php _e('Postname', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_parent"><?php _e('Parent', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_post_type"><?php _e('Type', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_comment_count"><?php _e('Comments', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						
    						<th class="tsinf_comfortdb_column_headline col_data_created"><?php _e('Created', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    						<th class="tsinf_comfortdb_column_headline col_data_modified"><?php _e('Modified', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
    					</tr>
					</thead>
				<?php
				$loopcount = 0;
				foreach($search_results as $sresult)
				{
					$loopcount++;
					
					$even_odd = 'odd';
					if($loopcount %2 === 0)
					{
						$even_odd = 'even';
					}
					
					$post_data = get_post($sresult->post_id);
					?>
					<tr class="<?php echo $even_odd; ?>">
						<td class="col_data_id"><?php echo $post_data->ID; ?></td>
						<td class="col_data_author"><?php echo $post_data->post_author; ?></td>
						<td class="col_data_title"><a href="<?php echo admin_url(sprintf("post.php?post=%d&action=edit", $post_data->ID)); ?>" target="_blank"><?php echo $post_data->post_title; ?></a></td>
						<td class="col_data_status"><?php echo $post_data->post_status; ?></td>
						<td class="col_data_postname"><?php echo $post_data->post_name; ?></td>
						<td class="col_data_parent"><?php echo $post_data->post_parent; ?></td>
						<td class="col_data_post_type"><?php echo $post_data->post_type; ?></td>
						<td class="col_data_comment_count"><?php echo $post_data->comment_count; ?></td>
						<td class="col_data_created">
							<?php echo $post_data->post_date; ?>
							<hr />
							<?php echo $post_data->post_date_gmt; ?> (GMT)
						</td>
						<td class="col_data_modified">
							<?php echo $post_data->post_modified; ?>
							<hr />
							<?php echo $post_data->post_modified_gmt; ?> (GMT)
						</td>
					</tr>
					<?php 
				}
				?>
				</table>
				<div class="tsinf_ps_load_more_container">
					<span class="tsinf_ps_load_more_button tsinf_symbol_button"><?php _e('Load more Results', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
				</div>
				<?php 
			}
			?>
			</div>
			<?php
		}
    }
    
    new TS_INF_GLOBAL_POST_SEARCH();
}