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


define('TSINF_DB_EXPORTER_NONCE', 'ctfD-2CXAq#NAx3Z756bWz');

if(!class_exists('TSINF_DB_EXPORTER'))
{
	
	/**
	 * Create Upload Folder
	 */
	function tsinf_database_export_create_upload_subfolder() {
	    $upload_dir = sprintf("%s/wp-content/ts-plugins/ts-database-exporter/", get_home_path());
	    if(!file_exists($upload_dir)) {
	        wp_mkdir_p($upload_dir);
	    }
	}
	
	register_activation_hook( __FILE__, 'tsinf_database_export_create_upload_subfolder' );
	
	class TSINF_DB_EXPORTER
	{
	    private static $plugin_path;
	    private static $upload_directory;
	    
	    private static $progress_hash;
	    
	    private static $progress_percent_total;
	    private static $progress_percent_table;
	    private static $progress_message;
	    private static $selected_tables_total;
	    private static $done_tables;
	    private static $current_table;
	    private static $current_table_entries_total;
	    private static $current_table_entries_done;
	    private static $current_script_filename;
	    private static $filemanager;
	    
	    private static $plugin_details;
	    	    
		public function __construct()
		{
			self::establish_connections();
		}

		/**
		 * Establish Connections
		 */
		public static function establish_connections()
		{
			add_action('admin_menu', array(__CLASS__, 'register_main_page'), 999);
			add_action('admin_init', array(__CLASS__, 'initialize_variables'));
			add_action('admin_enqueue_scripts', array(__CLASS__, 'load_backend_scripts'));
			add_action('wp_ajax_tsinf-db-export-start', array(__CLASS__, 'ajax_start_export'));
			add_action('admin_notices', array(__CLASS__,'admin_notices_errors'));
		}
			
		/**
		 * Output error messages
		 */
		public static function admin_notices_errors() {
		    $class = 'notice notice-error';
		    $message = null;
		    
		    if(!file_exists(self::$upload_directory)) 
		    {
		        $message = __('TS Comfort Database: Upload directory not exists!', 'tsinf_comfortdb_plugin_textdomain');
		    }
		    
		    if(file_exists(self::$upload_directory) && !is_writable(self::$upload_directory)) 
		    {
		        $message = __('TS Comfort Database: Upload directory is not writable!', 'tsinf_comfortdb_plugin_textdomain');
		    }
		    
		    if(!is_null($message))
		    {
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		    }
		}
		
		/**
		 * Initialize variables
		 */
		public static function initialize_variables()
		{
		    self::$plugin_path = plugins_url(plugin_basename( __FILE__ ));
		    self::$upload_directory = TS_INF_COMFORT_DB_UPLOAD_DIR;
		    self::$filemanager = new TSINF_FILEMANAGER(self::$upload_directory, array('tscomfortdb-filemanager'));
		    self::$plugin_details = get_plugin_data(__FILE__);
		    self::$progress_hash = sprintf("tsinf_exporter_state_%s", md5(get_current_user_id()));
		    
		    self::$progress_percent_total = 0;
		    self::$progress_percent_table = 0;
		    self::$progress_message = '';
		    
		    self::$selected_tables_total = 0;
		    self::$done_tables = array();
		    self::$current_table = '';
		    self::$current_table_entries_total = 0;
		    self::$current_table_entries_done = 0;
		}

		/**
		 * Load CSS and JavaScript needed for exporter
		 */
		public static function load_backend_scripts()
		{		    
		    wp_enqueue_style('ts-db-export-backend-css', plugins_url("../css/exporter.css", __FILE__), array('ts_comfort_db_main_css', 'ts_comfort_db_overview_css', 'ts_comfort_db_table_css', 'ts_comfort_db_edit_dataset_css'));
		    wp_enqueue_script('ts-db-export-backend-js', plugins_url("../js/exporter.js", __FILE__), array('jquery', 'ts_comfort_db_main_js', 'ts_comfort_db_table_js', 'ts_comfort_db_overview_js', 'ts_comfort_db_editform_js'));
		    wp_localize_script('ts-db-export-backend-js', 'TS_EXPDB_JS_CLASS', array(
		        'ajaxurl' => admin_url('admin-ajax.php'),
		        'checkerurl' => plugins_url('../checker.php', __FILE__),
		        'nonce' => wp_create_nonce(TSINF_DB_EXPORTER_NONCE),
		        'progress_hash' => self::$progress_hash,
		        'export_finished' => __('Export ist finished. Go to File Manager and download your file: ', 'tsinf_comfortdb_plugin_textdomain'),
		        'filemanager_name' => __('File Manager'),
		        'filemanager_url' => admin_url('admin.php?page=tscomfortdb-filemanager', 'tsinf_comfortdb_plugin_textdomain')
		    ));
		    
		    wp_enqueue_style('ts-db-export-filter-box-css', plugins_url("../css/filter_box.css", __FILE__));
		    wp_enqueue_script('ts-db-export-filter-box-js', plugins_url("../js/filter_box.js", __FILE__), array('jquery'));
		}

        /**
         * Register needed admin pages
         */		
		public static function register_main_page()
		{
		    add_submenu_page(
			        'tscomfortdb-mainpage',
					__('SQL Export BETA', 'tsinf_comfortdb_plugin_textdomain'),
					__('SQL Export BETA', 'tsinf_comfortdb_plugin_textdomain'),
					'manage_options',
					'tsinf_database_exporter_plugin_page',
					array(__CLASS__, 'render_main_page')
				);
		    
		    add_submenu_page(
    		        'tscomfortdb-mainpage',
    		        __('File Manager', 'tsinf_comfortdb_plugin_textdomain'),
    		        __('File Manager', 'tsinf_comfortdb_plugin_textdomain'),
    		        'manage_options',
    		        'tscomfortdb-filemanager',
    		        array(__CLASS__, 'render_file_manager')
		        );
		}
		
		/**
		 * Redner Exporter Main Page
		 */
		public static function render_main_page()
		{
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __('You do not have the permission to call this page.', 'tsinf_comfortdb_plugin_textdomain'));
			}
			
			
			?>
			<div class="tsinf-db-export-main-page-wrapper">
    			<h1><?php _e('SQL Exporter BETA', 'tsinf_comfortdb_plugin_textdomain'); ?></h1>

    			<form method="post" id="ts-db-export-start-export-settings-form" data-auth="<?php echo wp_create_nonce('ts-db-export-start-export-settings-form'); ?>">
    				<div id="tssesl_gps_filter_box_tables" class="tssesl_gps_filter_box sticky_header width-60">
						<span class="filter_box_headline tsinf_search_slug_line blue">
							<span class="tssesl_gps_filter_box_col width-60"><?php _e('Tables', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"><?php _e('Size', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"><?php _e('Entries', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"><?php _e('Structure', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"><?php _e('Data', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
						</span>
						<span class="tsinf_search_slug_line">
							<span class="tssesl_gps_filter_box_col width-60"></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"><input type="checkbox" class="tsinf_check_all" value="1" /></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center"><input type="checkbox" class="tsinf_check_all" value="1" /></span>
						</span>
						<?php 
						$db_table_counter = 0;
						$db_tables = TS_INF_COMFORT_DB_DATABASE::get_tables();
						foreach($db_tables as $table)
    					{        					    
    					    $db_table_counter++;
    					    
    					    $checked = 'checked="checked"';
    					    $checked_structure = 'checked="checked"';
    					    $checked_data = 'checked="checked"';
    					    
    					    $class_even_odd = "odd";
    					    if($db_table_counter % 2 === 0)
    					    {
    					        $class_even_odd = "even";
    					    }
    					    
    					    $table_size_col = '<img alt="" src="' . plugins_url("../images/loading-symbol.svg", __FILE__) . '" width="15px" height="auto" />';        					    
						?>
						<span class="tsinf_search_slug_line <?php echo $class_even_odd; ?>" data-table-name="<?php echo $table->TABLE_NAME; ?>" data-auth="<?php echo wp_create_nonce(sprintf('tsinf_comfortdb_table_overview_row_%s', $table->TABLE_NAME)); ?>">
							<span class="tssesl_gps_filter_box_col width-60">
								<?php echo $table->TABLE_NAME; ?>
							</span>
							<span class="tssesl_gps_filter_box_col tsinf_comfortdb_used_space width-10 text-center"><?php echo $table_size_col; ?></span>
							<span class="tssesl_gps_filter_box_col tsinf_comfortdb_entries width-10 text-center"><?php echo $table->TABLE_ROWS; ?></span>
							<span class="tssesl_gps_filter_box_col width-10 text-center">
								<input type="checkbox" name="ts_comfort_database_exporter[tables][<?php echo $table->TABLE_NAME; ?>][structure][]" <?php echo $checked_structure; ?> value="1" />
							</span>
							<span class="tssesl_gps_filter_box_col width-10 text-center">
								<input type="checkbox" name="ts_comfort_database_exporter[tables][<?php echo $table->TABLE_NAME; ?>][data][]" <?php echo $checked_data; ?> value="1" />
							</span>
						</span>
						<?php
					}
					?>
					</div>
					<div id="tssesl_gps_filter_box_options" class="tssesl_gps_filter_box sticky_header width-30">
						<span class="filter_box_headline tsinf_search_slug_line blue">
							<span class="tssesl_gps_filter_box_col width-100"><?php _e('Options', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
						</span>
						<span class="tsinf_search_slug_line">
							<span class="tssesl_gps_filter_box_col width-5">
								<input type="checkbox" class="option_create_db" name="ts_comfort_database_exporter[options][create_db][]" value="1" /> 
							</span>
							<span class="tssesl_gps_filter_box_col width-95">
								<?php _e('Add CREATE DATABASE (Ignored on Split Export)', 'tsinf_comfortdb_plugin_textdomain'); ?>
							</span>
						</span>
						<span class="tsinf_search_slug_line">
							<span class="tssesl_gps_filter_box_col width-5">
								<input type="checkbox" class="option_disable_fk_check" name="ts_comfort_database_exporter[options][disable_fk_check][]" checked="checked" value="1" />
							</span>
							<span class="tssesl_gps_filter_box_col width-95">
								<?php _e('Disable Foreign Key Checks', 'tsinf_comfortdb_plugin_textdomain'); ?>
							</span>
						</span>
						<span class="tsinf_search_slug_line">
							<span class="tssesl_gps_filter_box_col width-5">
								<input type="checkbox" class="option_enable_transaction" name="ts_comfort_database_exporter[options][enable_transaction][]" value="1" />
							</span>
							<span class="tssesl_gps_filter_box_col width-95">
								<?php _e('Summarize Script(s) in a TRANSACTION', 'tsinf_comfortdb_plugin_textdomain'); ?>
							</span>
						</span>
						<span class="tsinf_search_slug_line">
							<span class="tssesl_gps_filter_box_col width-5">
								<input type="checkbox" class="option_split_by_table" name="ts_comfort_database_exporter[options][split_by_table][]" value="1" />
							</span>
							<span class="tssesl_gps_filter_box_col width-95">
								<?php _e('Create seperate files per table (Split Export)', 'tsinf_comfortdb_plugin_textdomain'); ?>
							</span>
						</span>
						<span class="tsinf_search_slug_line split_by_lines">
							<span class="tssesl_gps_filter_box_col width-5">
								<input type="checkbox" class="option_split_by_table" name="ts_comfort_database_exporter[options][split_by_lines][]" value="1" />
							</span>
							<span class="tssesl_gps_filter_box_col width-95">
								<?php 
								$split_by_line_input = '<input type="number" class="option_split_by_line_number" name="ts_comfort_database_exporter[options][split_by_lines][number][]" value="1000" />';
								_e(sprintf('Begin new file after %s SQL-Statements', $split_by_line_input), 'tsinf_comfortdb_plugin_textdomain'); 
								?>
							</span>
						</span>
						
						
						</span>
					</div>
					
					<div id="tsinf-db-export-info-area">
        				<div class="progress-table-wrap">
        					<progress class="tsinf-progress-bar progress-table" value="0.0" max="1"></progress>
        					<span class="progress-current-entry"></span>
        					<span class="progress-table-number">0%</span>
        				</div>
        				<div class="progress-total-wrap">
        					<progress class="tsinf-progress-bar progress-total" value="0.0" max="1"></progress>
        					<span class="progress-current-table"></span>
        					<span class="progress-total-number">0%</span>
        				</div>
        				<div class="tsinf_comfort_database_progress_success progress-success"></div>
        				<div class="tsinf_comfort_database_progress_error progress-error"></div>
        			</div>
        			
        			<div class="tsinf_db_exporter_submit_container">
    					<input type="submit" class="tsinf_symbol_button" id="ts-db-export-start-export" name="ts-db-export-start-export" value="<?php _e('Start Export', 'tsinf_comfortdb_plugin_textdomain'); ?>" />
					</div>
    			</form>
    			<?php 
		}
		
		/**
		 * Call render file manager method to render file manager page
		 */
		public static function render_file_manager()
		{
		    if ( !current_user_can( 'manage_options' ) )  {
		        wp_die( __('You do not have the permission to call this page.', 'tsinf_comfortdb_plugin_textdomain'));
		    }
		    
		    ?>
            <h1><?php _e('File Manager', 'tsinf_comfortdb_plugin_textdomain'); ?></h1>
            <?php
		    self::$filemanager->render_file_manager();
		}
		
		/**
		 * Exporter function which does the export work
		 * @param array $target_tables tables to export
		 * @param array $options addional options split_by_table | split_by_lines | create_db | disable_fk_check | enable_transaction
		 */
		private static function exporter($target_tables, $options = array())
		{
		    if(file_exists(self::$upload_directory) && is_writable(self::$upload_directory)) {
		        add_action('shutdown', function()
		        {
		            if($error = error_get_last())
		            {
		                // Catch Fatal Errors
		                self::write_error($error['message']);
		            }
		        });
		        
		        try {
        		    global $wpdb;
        		    
        		    $sql_statement_counter = 0;
        		    
        		    $target_table_names = array_keys($target_tables);
        
        		    self::$progress_message = __('Exporting ...', 'tsinf_comfortdb_plugin_textdomain');
        		    self::$selected_tables_total = count($target_tables);
        		    
        		    self::$current_script_filename = sprintf("%s", date("Y-m-d-H-i-s-"));
        		    if(self::$selected_tables_total > 1)
        		    {
        		        // more than one table - Write Database name in filename
        		        self::$current_script_filename .= DB_NAME;
        		    } else if(self::$selected_tables_total > 0) {
        		        // Only one table - Write table name in filename
        		        self::$current_script_filename .= DB_NAME . "-" . reset($target_tables);
        		    } else {
        		        return;
        		    }
        		    		    
        		    self::$current_script_filename .= ".sql";
        		    
        		    $split_by_table = (bool) false;
        		    if(isset($options['split_by_table']) && $options['split_by_table'] === true)
        		    {
        		        $split_by_table = (bool) true;
        		    }
        		    
        		    $split_by_lines = (bool) false;
        		    $split_by_lines_number = -1;
        		    if(isset($options['split_by_lines']) && $options['split_by_lines'] === true)
        		    {
        		        if(isset($options['split_by_lines']['number']))
        		        {
        		            $split_by_lines_number = (int) $options['split_by_lines']['number'];
        		            
        		            if($split_by_lines_number > 0)
        		            {
        		                $split_by_lines = (bool) true;
        		            }
        		        }
        		    }
        		    
        		    $fp = null;
        		    if($split_by_table !== true)
        		    {
        		        $fp = fopen(self::$upload_directory . self::$current_script_filename, 'w');
        		    }
        		                
                    if(isset($options['create_db']) && $options['create_db'] === true && $split_by_table !== true)
                    {
                        fwrite($fp, PHP_EOL . sprintf("CREATE DATABASE %s;", DB_NAME));
                        fwrite($fp, PHP_EOL . sprintf("USE %s;", DB_NAME));
                        
                        $sql_statement_counter++;
                        $sql_statement_counter++;
                    }
                    
                    if(isset($options['disable_fk_check']) && $options['disable_fk_check'] === true && $split_by_table !== true)
                    {
                        fwrite($fp, PHP_EOL . "SET FOREIGN_KEY_CHECKS=0;");
                        $sql_statement_counter++;
                    }
                    
                    if(isset($options['enable_transaction']) && $options['enable_transaction'] === true && $split_by_table !== true)
                    {
                        fwrite($fp, "START TRANSACTION;");
                        $sql_statement_counter++;
                    }
                    
                    $split_by_table_date_base = '';
                    if($split_by_table === true)
                    {
                        $split_by_table_date_base = date("Y-m-d-H-i-s-");
                    }
                    
                    $table_counter = 0;
                    $split_by_line_file_counter = 0;
                    if(is_array($target_table_names) && count($target_table_names) > 0)
                    {
                        foreach($target_table_names as $table)
            		    {
            		        $progress = get_transient(self::$progress_hash);
            		        
            		        if($split_by_table === true)
            		        {
            		            self::$current_script_filename = sprintf("%s", $split_by_table_date_base) . DB_NAME . "-" . $table . ".sql";
            		            $fp = fopen(self::$upload_directory . self::$current_script_filename, 'w');
            		            
            		            if(isset($options['disable_fk_check']) && $options['disable_fk_check'] === true)
            		            {
            		                fwrite($fp, PHP_EOL . "SET FOREIGN_KEY_CHECKS=0;");
            		                $sql_statement_counter++;
            		            }
            		            
            		            if(isset($options['enable_transaction']) && $options['enable_transaction'] === true)
            		            {
            		                fwrite($fp, "START TRANSACTION;");
            		                $sql_statement_counter++;
            		            }
            		        }
            		        
            		        if($split_by_lines === true && $sql_statement_counter >= $split_by_lines_number)
            		        {
            		            $split_by_line_file_counter++;
            		            
            		            $next_file_name_base = explode(".sql", self::$current_script_filename);
            		            if(is_array($next_file_name_base) && count($next_file_name_base) > 0)
            		            {
            		                $next_file_name_base = reset($next_file_name_base);
            		                $next_file_name_base .= "-" . $split_by_line_file_counter . ".sql";
            		            
                		            fclose($fp);
                		            $fp = fopen(self::$upload_directory . $next_file_name_base, 'w');
                		            
                		            $sql_statement_counter = 0;
            		            }
            		        }
            		        
            		        $table_counter++;
            		        $export_structure = (isset($target_tables[$table]['structure']) && count($target_tables[$table]['structure']) > 0 && (int) $target_tables[$table]['structure'][0] === 1);
            		        $export_data = (isset($target_tables[$table]['data']) && count($target_tables[$table]['data']) > 0 && (int) $target_tables[$table]['data'][0] === 1);
            		        
            		        if($export_structure === true)
            		        {
            		            $create_table = "";
            		            $create_table_arr = $wpdb->get_row('SHOW CREATE TABLE '.$table, ARRAY_A);
            		            if(isset($create_table_arr["Create Table"]))
            		            {
            		                $create_table = PHP_EOL . $create_table_arr["Create Table"] . ";";
            		                fwrite($fp, $create_table);
            		                $sql_statement_counter++;
            		            }
            		        }
            		        
            		        if($export_data === true)
            		        {
                		        self::$current_table = $table;
                		        
                		        $current_table_column_names = TS_INF_COMFORT_DB_DATABASE::get_column_names($table);
                		        
                		        // Escape Line-Breaks
                		        $select_str_arr = array();
                		        if(is_array($current_table_column_names) && count($current_table_column_names) > 0)
                		        {
                		            foreach($current_table_column_names as $col_name)
                		            {
                		                $select_str_arr[] = sprintf("REPLACE(REPLACE(`%s`, CHAR(10), '\\\\n'), CHAR(13), '\\\\r') as %s", $col_name, $col_name);
                		            }
                		        }
                		        
                		        $select_str = implode(",", $select_str_arr); 
                		        
                		        $table_data = array();
                		        
                		        $table_data_sql = sprintf("SELECT %s FROM `%s`;", $select_str, $table);
                		        $table_data = $wpdb->get_results($table_data_sql, ARRAY_A);
                		        
                		        if(is_array($table_data) && count($table_data) > 0)
                		        {
                		            self::$current_table_entries_total = count($table_data);
                		            
                		            $table_entry_counter = 0;
                                    foreach($table_data as $dataset)
                    		        {
                    		            self::$current_table_entries_done = $table_entry_counter;
                    		            if(self::$current_table_entries_total > 0)
                    		            {
                                            self::$progress_percent_table = ($table_entry_counter / self::$current_table_entries_total);
                    		            }
                    		            
                    		            $keys = array_keys($dataset);
                    		            
                    		            $keys_quoted = array_map(function($key) { return "`" . $key . "`"; }, $keys);
                    		            
                    		            $values = array_values($dataset);
                    		            
                    		            $keys_str = implode(",", $keys_quoted);
                    		            $value_str = '';
                    		            
                    		            $values_count = count($values);
                    		            $value_counter = 0;
                    		            foreach($values as $value)
                    		            {
                    		                $value_counter++;
                    		                
                    		                $val = $value;
                    		                if(is_null($value))
                    		                {
                    		                    $val = 'NULL';
                    		                } else {
                    		                    $val = addslashes($value);
                    		                    $val = "'" . $val . "'";
                    		                }
                    		                
                    		                $value_str .= $val;
                    		                if($value_counter < $values_count)
                    		                {
                    		                    $value_str .= ",";
                    		                }		                
                    		            }
                    		            
                    		            $insert = PHP_EOL . sprintf("INSERT INTO `" . $table . "` (%s) VALUES (%s);", $keys_str, $value_str);
                    		            
                    		            fwrite($fp, $insert);
                    		            $sql_statement_counter++;
                    		            
                    		            $table_entry_counter++;
                    		        }
                		        }
            
                		        fwrite($fp, PHP_EOL . PHP_EOL . PHP_EOL);
                		        
                		        if($split_by_table === true)
                		        {
                		            if(isset($options['enable_transaction']) && $options['enable_transaction'] === true)
                		            {
                		                fwrite($fp, "COMMIT;");
                		                $sql_statement_counter++;
                		            }
                		            
                		            if(isset($options['disable_fk_check']) && $options['disable_fk_check'] === true)
                		            {
                		                fwrite($fp, PHP_EOL . "SET FOREIGN_KEY_CHECKS=1;");
                		                $sql_statement_counter++;
                		            }
                		            
                		            fclose($fp);
                		        }
                		    }
                		    
                		    self::$done_tables[] = self::$current_table;
                		    
                		    self::$progress_percent_total = (float) ($table_counter / self::$selected_tables_total);
                		    
                		    self::write_progress();
            		    }
            		    
                    
                    }
                    
                    if(isset($options['enable_transaction']) && $options['enable_transaction'] === true && $split_by_table !== true)
                    {
                        fwrite($fp, "COMMIT;");
                        $sql_statement_counter++;
                    }
                    
                    if(isset($options['disable_fk_check']) && $options['disable_fk_check'] === true && $split_by_table !== true)
                    {
                        fwrite($fp, PHP_EOL . "SET FOREIGN_KEY_CHECKS=1;");
                        $sql_statement_counter++;
                    }
                    
                    if(is_resource($fp))
                    {
                        fclose($fp);
                    }
                
                
                 } catch(Exception $exception)
                 {
                     self::write_error($exception->getMessage());
                     error_log(get_transient(self::$progress_hash));
                 }
                 
		    } else {
		        self::write_error(__('Upload Directory not exists', 'tsinf_comfortdb_plugin_textdomain') . ': ' . self::$upload_directory);
		    }
		}
		
		/**
		 * Write current progress to transient
		 */
		public static function write_progress()
		{
		    $result = array();
		    $result['percent_total'] = self::$progress_percent_total;
		    $result['percent_table'] = self::$progress_percent_table;    
		    $result['message'] = self::$progress_message;

		    $result['selected_tables_total'] = self::$selected_tables_total;
		    $result['done_tables'] = self::$done_tables;
		    $result['current_table'] = self::$current_table;
		    $result['current_table_entries_total'] = self::$current_table_entries_total;
		    $result['current_table_entries_done'] = self::$current_table_entries_done;
		    		    
		    $result_serialized = json_encode($result);
		    
		    set_transient(self::$progress_hash, $result_serialized, HOUR_IN_SECONDS);
		    
		}
		
		/**
		 * Write error to transient
		 * @param string $error Error Message
		 */
		public static function write_error($error)
		{
		    $result = array();
		    $result['percent_total'] = self::$progress_percent_total;
		    $result['percent_table'] = self::$progress_percent_table;
		    $result['message'] = self::$progress_message;
		    
		    $result['selected_tables_total'] = self::$selected_tables_total;
		    $result['done_tables'] = self::$done_tables;
		    $result['current_table'] = self::$current_table;
		    $result['current_table_entries_total'] = self::$current_table_entries_total;
		    $result['current_table_entries_done'] = self::$current_table_entries_done;
		    $result['error'] = $error;
		    		    
		    $result_serialized = json_encode($result);
		    
		    set_transient(self::$progress_hash, $result_serialized, HOUR_IN_SECONDS);
		}
		
		/**
		 * AJAX Callback to start export
		 */
		public static function ajax_start_export()
		{
		    check_ajax_referer('ts-db-export-start-export-settings-form', 'security', true);
		    
		    $form_data = array();
		    if(isset($_POST['form_data']))
		    {
		        parse_str($_POST['form_data'], $form_data);
		    }
		    
		    $export_target = '';
		    if(isset($form_data["ts_comfort_database_exporter"]["options"]["export_target"]) && is_array($form_data["ts_comfort_database_exporter"]["options"]["export_target"]) && count($form_data["ts_comfort_database_exporter"]["options"]["export_target"]) > 0)
		    {
		        $export_target = array_pop($form_data["ts_comfort_database_exporter"]["options"]["export_target"]);
		    }
		    
		    $param_create_db = (bool) false;
		    if(isset($form_data["ts_comfort_database_exporter"]["options"]['create_db']) && is_array($form_data["ts_comfort_database_exporter"]["options"]['create_db']) && count($form_data["ts_comfort_database_exporter"]["options"]['create_db']) > 0)
		    {
		        $create_db = array_pop($form_data["ts_comfort_database_exporter"]["options"]["create_db"]);
		        if((int) $create_db === 1)
		        {
		            $param_create_db = (bool) true;
		        }
		    }
		    
		    $param_fk_check = (bool) false;
		    if(isset($form_data["ts_comfort_database_exporter"]["options"]['disable_fk_check']) && is_array($form_data["ts_comfort_database_exporter"]["options"]['disable_fk_check']) && count($form_data["ts_comfort_database_exporter"]["options"]['disable_fk_check']) > 0)
		    {
		        $disable_fk_check = array_pop($form_data["ts_comfort_database_exporter"]["options"]["disable_fk_check"]);
		        if((int) $disable_fk_check === 1)
		        {
		            $param_fk_check = (bool) true;
		        }
		    }
		    
		    $param_enable_transaction = (bool) false;
		    if(isset($form_data["ts_comfort_database_exporter"]["options"]['enable_transaction']) && is_array($form_data["ts_comfort_database_exporter"]["options"]['enable_transaction']) && count($form_data["ts_comfort_database_exporter"]["options"]['enable_transaction']) > 0)
		    {
		        $enable_transaction = array_pop($form_data["ts_comfort_database_exporter"]["options"]["enable_transaction"]);
		        if((int) $enable_transaction === 1)
		        {
		            $param_enable_transaction = (bool) true;
		        }
		    }
		    
		    $param_split_by_table = (bool) false;
		    if(isset($form_data["ts_comfort_database_exporter"]["options"]['split_by_table']) && is_array($form_data["ts_comfort_database_exporter"]["options"]['split_by_table']) && count($form_data["ts_comfort_database_exporter"]["options"]['split_by_table']) > 0)
		    {
		        $param_split_by_table = array_pop($form_data["ts_comfort_database_exporter"]["options"]["split_by_table"]);
		        if((int) $param_split_by_table === 1)
		        {
		            $param_split_by_table = (bool) true;
		        }
		    }
		    
		    $exporter_options = array(
		        'create_db' => $param_create_db,
		        'disable_fk_check' => $param_fk_check,
		        'enable_transaction' => $param_enable_transaction,
		        'split_by_table' => $param_split_by_table
		    );

		    self::exporter($form_data["ts_comfort_database_exporter"]["tables"], $exporter_options);
		    
		    wp_die();
		}
		
	}
	
	new TSINF_DB_EXPORTER();
}