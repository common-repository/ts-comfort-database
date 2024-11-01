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

define("TSINF_COMFORT_DB_SET_COL_VALUE_NULL", "TSINF_COMFORT_DB_SET_COL_VALUE_NULL_i5OBxV9hcfEAGLQqzsUg");

/**
 * Plugin Main Class
 * @author Tobias Spiess
 */
class TS_INF_COMFORT_DB {
    private static $query_limit;
    private static $show_adminbar_menu;
    
    private static $progress_hash_table_to_csv;
    private static $progress_hash_rows_to_csv;
    
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
        $limit = (int) get_option('rows_per_site');
        if($limit > 0 && $limit <= 100)
        {
            self::$query_limit = $limit;
        } else {
            self::$query_limit = 20;
        }
        
        $show_adminbar_menu = (int) get_option('tscdb_show_adminbar_menu');
        self::$show_adminbar_menu = ($show_adminbar_menu === 1);
        
        // Must be an array because needs diffferent hashes if user clicks more than one table to export
        self::$progress_hash_table_to_csv = array();
        $table_names = TS_INF_COMFORT_DB_DATABASE::get_table_names();
        if(is_array($table_names) && count($table_names) > 0)
        {
            foreach($table_names as $tablename)
            {
                self::$progress_hash_table_to_csv[$tablename] = sprintf("tsinf_table_to_csv_state_%s", md5(get_current_user_id() . $tablename));
            }
        }
        
        self::$progress_hash_rows_to_csv = sprintf("tsinf_rows_to_csv_state_%s", md5(get_current_user_id()));
        
        $skin = get_option('tscdb_skin');
        self::$valid_skins = array("wordpress", "tscdb");
        self::$skin = (is_string($skin) && strlen($skin) > 0 && in_array($skin, self::$valid_skins)) ? $skin : "tscdb";
        
        add_action('admin_menu', array('TS_INF_COMFORT_DB', 'generate_menu'), 10);
		add_action('admin_enqueue_scripts', array('TS_INF_COMFORT_DB', 'load_backend_scripts'));
		
		if(self::$show_adminbar_menu === true)
		{
		  add_action('admin_bar_menu', array('TS_INF_COMFORT_DB', 'add_tables_to_adminbar'), 999);
		}
		
		add_action('wp_ajax_get_table_struct_meta', array('TS_INF_COMFORT_DB', 'ajax_get_table_struct_meta_callback'));
		add_action('wp_ajax_get_column_meta_data', array('TS_INF_COMFORT_DB', 'ajax_get_column_meta_data_callback'));
		
		add_action('wp_ajax_export_table_to_csv', array('TS_INF_COMFORT_DB', 'ajax_export_table_to_csv_callback'));
		add_action('wp_ajax_export_table_to_csv', array('TS_INF_COMFORT_DB', 'ajax_export_table_to_csv_callback'));
		
		add_action('wp_ajax_export_rows_to_csv', array('TS_INF_COMFORT_DB', 'ajax_export_rows_to_csv_callback'));
		add_action('wp_ajax_export_rows_to_csv', array('TS_INF_COMFORT_DB', 'ajax_export_rows_to_csv_callback'));
		
        add_action('wp_ajax_tsinf_comfortdb_table_delete_rows', array('TS_INF_COMFORT_DB', 'ajax_tsinf_comfortdb_table_delete_rows'));
        add_action('wp_ajax_risk_message_button_confirmed', array('TS_INF_COMFORT_DB', 'ajax_risk_message_button_confirmed'));
        add_action('wp_ajax_table_context_menu', array('TS_INF_COMFORT_DB', 'ajax_table_context_menu'));
        add_action('admin_init', array('TS_INF_COMFORT_DB','render_admin_page_settings_options'));
        add_action('wp_loaded', array('TS_INF_COMFORT_DB','helper_redirect_to_search_results_page'));
        
        add_filter('admin_body_class', array('TS_INF_COMFORT_DB', 'modify_admin_body_classes'));
        add_filter('admin_title', array('TS_INF_COMFORT_DB', 'modify_admin_title'), 10, 2);
                
        if(!file_exists(TS_INF_COMFORT_DB_UPLOAD_DIR))
        {
            try {
                $result = wp_mkdir_p(TS_INF_COMFORT_DB_UPLOAD_DIR);
                if($result === false)
                {
                    // Directory could not be created
                }
            } catch(Exception $e)
            {
                
            }
        }
	}
	
    /**
	 * Load Frontend Scripts and Stylesheets
	 */
	public static function load_frontend_scripts()
	{
	}
	
    /**
	 * Load Backend Scripts and Stylesheets
	 */
	public static function load_backend_scripts()
	{
        wp_enqueue_style( 'ts_comfort_db_main_css', plugins_url('../css/main.css', __FILE__) );
		wp_enqueue_style( 'ts_comfort_db_overview_css', plugins_url('../css/overview.css', __FILE__) );
		wp_enqueue_style( 'ts_comfort_db_table_css', plugins_url('../css/table.css', __FILE__) );
        wp_enqueue_style( 'ts_comfort_db_edit_dataset_css', plugins_url('../css/edit_dataset.css', __FILE__) );
        
        wp_enqueue_script( 'ts_comfort_db_main_js', plugins_url('../js/main.js', __FILE__), array('jquery') );
        wp_enqueue_script( 'ts_comfort_db_table_js', plugins_url('../js/table.js', __FILE__), array('jquery', 'ts_comfort_db_main_js') );
        wp_enqueue_script( 'ts_comfort_db_table_ajax_js', plugins_url('../js/table.ajax.js', __FILE__), array('jquery') );
        wp_enqueue_script( 'ts_comfort_db_overview_js', plugins_url('../js/overview.js', __FILE__), array('jquery', 'ts_comfort_db_main_js') );
        wp_enqueue_script( 'ts_comfort_db_editform_js', plugins_url('../js/editform.js', __FILE__), array('jquery') );
        
        wp_localize_script('ts_comfort_db_table_ajax_js', 'TSINF_COMFORT_DB_TABLE_AJAX_JS', array(
            'primary_key' => __('Primary Key', 'tsinf_comfortdb_plugin_textdomain'),
            'foreign_key' => __('Foreign Key', 'tsinf_comfortdb_plugin_textdomain'),
            'references' => __('REFERENCES', 'tsinf_comfortdb_plugin_textdomain'),
            'error_occured' => __('An error occured', 'tsinf_comfortdb_plugin_textdomain'),
            'error_post_data' => __('Invalid Post Data', 'tsinf_comfortdb_plugin_textdomain'),
            'error_row_data' => __('Invalid Row Data', 'tsinf_comfortdb_plugin_textdomain'),
            'error_query' => __('Query Error', 'tsinf_comfortdb_plugin_textdomain'),
            'error_server_response' => __('Server Response not readable', 'tsinf_comfortdb_plugin_textdomain')
        ));
        
        wp_localize_script('ts_comfort_db_table_js', 'TSINF_COMFORT_DB_TABLE_JS', array(
            'checkerurl' => plugins_url('../checker.php', __FILE__),
            'really_delete' => __('Do you really want to delete the selected rows?', 'tsinf_comfortdb_plugin_textdomain'),
            'copy_to_clipboard' => __('Copy to clipboard', 'tsinf_comfortdb_plugin_textdomain'),
            'nonce_cell_menu' => wp_create_nonce('tsinf_comfortdb_plugin_table_cell_menu'),
            'progress_hash_rows_to_csv' => self::$progress_hash_rows_to_csv,
            'export_finished' => __('Export ist finished. Go to File Manager and download your file: ', 'tsinf_comfortdb_plugin_textdomain'),
            'filemanager_name' => __('File Manager'),
            'filemanager_url' => admin_url('admin.php?page=tscomfortdb-filemanager', 'tsinf_comfortdb_plugin_textdomain')
        ));
        
        wp_localize_script('ts_comfort_db_overview_js', 'TSINF_COMFORT_OVERVIEW_JS', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'checkerurl' => plugins_url('../checker.php', __FILE__),
            'nonce' => wp_create_nonce(TS_INF_TABLE_TO_CSV_NONCE),
            'progress_hash_table_to_csv' => self::$progress_hash_table_to_csv,
            'export_finished' => __('Export ist finished. Go to File Manager and download your file: ', 'tsinf_comfortdb_plugin_textdomain'),
            'filemanager_name' => __('File Manager'),
            'filemanager_url' => admin_url('admin.php?page=tscomfortdb-filemanager', 'tsinf_comfortdb_plugin_textdomain')
        ));
	}
	
	/**
	 * Adds one or more classes to the body tag in the backend.
	 *
	 * @link https://wordpress.stackexchange.com/a/154951/17187
	 * @param  String $classes Current body classes.
	 * @return String          Altered body classes.
	 */
	public static function modify_admin_body_classes($classes) {
	    return "$classes tscdb_skin_" . self::$skin;
	}

    /**
	 * Change Admin Page Title
	 *
	 * @param  String $page_title Modified Page Title
     * @param  String $title Original Page Title
	 * @return String          Modified Page Title
	 */
    public static function modify_admin_title($page_title, $title)
    {
        if(isset($_GET['page']) && $_GET['page'] === 'tscomfortdb-mainpage')
        {
            if(isset($_GET['table']))
			{
				$tablename = htmlentities(strip_tags($_GET['table']), ENT_QUOTES);
                
                if(isset($_GET['action']) && $_GET['action'] === 'search') {
                    // SEARCH
                    if(isset($_GET['identifier']))
                    {
                        $prepend_table = __("Search Results for Table:", "tsinf_comfortdb_plugin_textdomain") . " " . htmlentities(strip_tags($_GET['table']), ENT_QUOTES);
                        $new_title = $prepend_table . " - " . $page_title;
                        $page_title = $new_title;
                    } else {
                        $prepend_table = htmlentities(strip_tags($_GET['table']), ENT_QUOTES);
                        $new_title = $prepend_table . " - " . $page_title;
                        $page_title = $new_title;
                    }
                } else if(isset($_GET['identifier']))
                {                    
                    $prepend_table = __("Edit Table:", "tsinf_comfortdb_plugin_textdomain") . " " . htmlentities(strip_tags($_GET['table']), ENT_QUOTES);
                    $new_title = $prepend_table . " - " . $page_title;
                    $page_title = $new_title;
                } else if(isset($_GET['action']) && $_GET['action'] === 'new') {
                    // CREATE NEW DATASET
                    $prepend_table = __("New Dataset:", "tsinf_comfortdb_plugin_textdomain");
                    $new_title = $prepend_table . " - " . $page_title;
                    $page_title = $new_title;
                } else {
                    // TABLE CONTENT
                    $prepend_table = htmlentities(strip_tags($_GET['table']), ENT_QUOTES);
                    $new_title = $prepend_table . " - " . $page_title;
                    $page_title = $new_title;
                }
			} else if(isset($_GET['tsinf_comfortdb_full_text_search']) && isset($_GET['table_identifier'])) {
                $prepend_table = __("Full Text Search Results for Table:", "tsinf_comfortdb_plugin_textdomain") . " " . htmlentities(strip_tags($_GET['table_identifier']), ENT_QUOTES);
                $new_title = $prepend_table . " - " . $page_title;
                $page_title = $new_title;
            } else if(isset($_GET['tsinf_comfortdb_full_text_search'])) {
                if(check_admin_referer( 'action_tsinf_comfortdb_plugin_full_text_search_submit', '_wpnonce' ))
                {
                    $prepend_table = __("Full Text Search Results", "tsinf_comfortdb_plugin_textdomain");
                    $new_title = $prepend_table . " - " . $page_title;
                    $page_title = $new_title;
                }
            } else {
                $prepend_table = __("Table Overview", "tsinf_comfortdb_plugin_textdomain");
                $new_title = $prepend_table . " - " . $page_title;
                $page_title = $new_title;
			}
        }

        return $page_title;
    }
	
    /**
	 * Create Wordpress Backend Menu and Submenus
	 */
	public static function generate_menu()
	{
        add_menu_page(
				__('Comfort DB', 'tsinf_comfortdb_plugin_textdomain'),
				__('Database', 'tsinf_comfortdb_plugin_textdomain'),
				'manage_options',
				'tscomfortdb-mainpage',
				array('TS_INF_COMFORT_DB', 'render_admin_page_main'),
                plugins_url('../images/Comfort-SQLite-Logo16x16.png', __FILE__)
		);
        
        add_submenu_page(
            'tscomfortdb-mainpage', 
            __('Comfort DB Settings', 'tsinf_comfortdb_plugin_textdomain'), 
            __('Settings', 'tsinf_comfortdb_plugin_textdomain'), 
            'manage_options', 
            'tscomfortdb-settings',
            array('TS_INF_COMFORT_DB', 'render_admin_page_settings')
        );
	}
	
    /**
	 * Decide what page to render depent on Plugin GET Parameters
	 */
	public static function render_admin_page_main()
	{
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
		if(isset($_GET['page']) && $_GET['page'] === 'tscomfortdb-mainpage')
		{
			if(isset($_GET['table']))
			{
				$tablename = htmlentities(strip_tags($_GET['table']), ENT_QUOTES);
                
                if(isset($_GET['action']) && $_GET['action'] === 'search') {
                    // SEARCH
                    if(isset($_GET['identifier']))
                    {
                        self::render_table_dataview($tablename);
                    } else {
                        self::render_dataset_input_form($tablename, array(), 'search');
                    }
                } else if(isset($_GET['identifier']))
                {
                    // EDIT DATASET
                    $identifier = strip_tags(htmlspecialchars($_GET['identifier']));
                    $identifier_array = explode("AND", $identifier);
                    
                    self::render_dataset_input_form($tablename, $identifier_array);
                } else if(isset($_GET['action']) && $_GET['action'] === 'new') {
                    // CREATE NEW DATASET
                    self::render_dataset_input_form($tablename);
                } else {
                    // TABLE CONTENT
				    self::render_table_dataview($tablename);
                }
			} else if(isset($_GET['tsinf_comfortdb_full_text_search']) && isset($_GET['table_identifier'])) {
                self::render_full_text_search_table();
            } else if(isset($_GET['tsinf_comfortdb_full_text_search'])) {
                if(check_admin_referer( 'action_tsinf_comfortdb_plugin_full_text_search_submit', '_wpnonce' ))
                {
                    self::render_full_text_search();
                }
            } else {
				self::render_table_overview();
			}
            
            self::render_admin_page_footer();
		}
	}
    
    /**
     * Render public page footer
     */
    public static function render_admin_page_footer()
    {
        ?>
        <div id="tsinf_comfortdb_plugin_admin_page_footer">
            <div id="tsinf_comfortdb_plugin_footer_credits">
            <?php
            _e('Loading Symbol made with loading.io', 'tsinf_comfortdb_plugin_textdomain');
            ?>
            </div>
        </div>
        <?php
    }
    
    /**
	 * Render Settings Page
	 */
    public static function render_admin_page_settings()
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        echo '<form method="POST" action="options.php">';
        settings_fields( 'tscomfortdb-settings' );
        do_settings_sections( 'tscomfortdb-settings' ); 
        submit_button();	
        echo '</form>';
    }
    
    /**
	 * Create and Render Options for Settings Page
	 */
    public static function render_admin_page_settings_options()
    {
        add_settings_section('tscomfortdb-main-settings',__('Comfort DB Settings', 'tsinf_comfortdb_plugin_textdomain'), array('TS_INF_COMFORT_DB','render_admin_page_settings_section_main'),'tscomfortdb-settings');
        add_settings_field('rows_per_site', __('Rows per Site (Limit)','tsinf_comfortdb_plugin_textdomain'), array('TS_INF_COMFORT_DB','render_admin_page_settings_field_rows_per_site'), 'tscomfortdb-settings', 'tscomfortdb-main-settings');
        add_settings_field('tscdb_show_adminbar_menu', __('Show Adminbar Menu','tsinf_comfortdb_plugin_textdomain'), array('TS_INF_COMFORT_DB','render_admin_page_settings_field_show_adminbar_menu'), 'tscomfortdb-settings', 'tscomfortdb-main-settings');
        add_settings_field('tscdb_disable_table_overview_meta', __('Disable Initial load of Meta Data in Table Overview','tsinf_comfortdb_plugin_textdomain'), array('TS_INF_COMFORT_DB','render_admin_page_settings_field_disable_table_overview_meta'), 'tscomfortdb-settings', 'tscomfortdb-main-settings');
        add_settings_field('tscdb_skin', __('Skin','tsinf_comfortdb_plugin_textdomain'), array('TS_INF_COMFORT_DB','render_admin_page_settings_field_skin'), 'tscomfortdb-settings', 'tscomfortdb-main-settings');
        register_setting( 'tscomfortdb-settings', 'rows_per_site' );
        register_setting( 'tscomfortdb-settings', 'tscdb_show_adminbar_menu' );
        register_setting( 'tscomfortdb-settings', 'tscdb_disable_table_overview_meta' );
        register_setting( 'tscomfortdb-settings', 'tscdb_skin' );
    }
    
    /**
	 * Render Settingspage Main Section Content
	 */
    public static function render_admin_page_settings_section_main()
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        echo '<p>' . __('Main Settings', 'tsinf_comfortdb_plugin_textdomain') . '</p>';
    }
    
    /**
     * Render Settingspage Limit / Rows per Page Control
     */
    public static function render_admin_page_settings_field_rows_per_site()
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $value = (int) get_option('rows_per_site');
        $steps = array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100);   
        
        $options_str = '';
        if(is_array($steps) && count($steps) > 0)
        {
            foreach($steps as $step)
            {
                $step_number = (int) $step;
                
                $selected = '';
                if($step_number === $value)
                {
                    $selected = ' selected="selected" ';
                }
                
                $options_str .= sprintf('<option %s value="%d">%d</option>', $selected, $step_number, $step_number);
            }
        }
        
        echo '<select name="rows_per_site" id="tsinf_comfortdb_plugin_setting_rows_per_site" class="tsinf_comfortdb_plugin_setting_rows_per_site">
                   ' . $options_str . '
                </select>';
    }
    
    /**
     * Render Show Adminbar Menu
     */
    public static function render_admin_page_settings_field_show_adminbar_menu()
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
        }
        
        $value = (int) get_option('tscdb_show_adminbar_menu');
                
        echo '<input type="checkbox" ' . checked($value, 1, false) . ' name="tscdb_show_adminbar_menu" class="tscdb_show_adminbar_menu" id="tscdb_show_adminbar_menu" value="1" />';
    }
    
    /**
     * Render Disable loading meta data in table overview
     */
    public static function render_admin_page_settings_field_disable_table_overview_meta()
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
        }
        
        $value = (int) get_option('tscdb_disable_table_overview_meta');
        
        echo '<input type="checkbox" ' . checked($value, 1, false) . ' name="tscdb_disable_table_overview_meta" class="tscdb_disable_table_overview_meta" id="tscdb_disable_table_overview_meta" value="1" />';
    }
    
    /**
     * Render Skin Selection
     */
    public static function render_admin_page_settings_field_skin()
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
        }
        
        $value = get_option('tscdb_skin');
        
        echo '<select name="tscdb_skin" id="tscdb_skin" class="tscdb_skin">
                   <option value="tscdb" ' . selected($value, "tscdb", false) . '>' . __('Comfort Database Style', 'tsinf_comfortdb_plugin_textdomain') . '</option>
                   <option value="wordpress" ' . selected($value, "wordpress", false) , '>' . __('WordPress Style', 'tsinf_comfortdb_plugin_textdomain') . '</option>
                </select>';
    }
    
    /**
	 * Render Input Form where user creates new dataset or edit a dataset
	 * @param string $tablename Table Name of table which contains the dataset to edit
	 * @param array $identifiers Array with identifier parameters which identifies the dataset (Primary Keys [keyname] => [keyvalue])
	 */
    private static function render_dataset_input_form($tablename, $identifiers = array(), $action = '')
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $action = trim($action);
        
        $headline = __('New Dataset','tsinf_comfortdb_plugin_textdomain');
        if(is_array($identifiers) && count($identifiers) > 0)
        {
            $headline = __('Edit Dataset','tsinf_comfortdb_plugin_textdomain');
        } else if(strlen($action) > 0 && $action === 'search')
        {
            $headline = __('Search Table','tsinf_comfortdb_plugin_textdomain');
        }
        
        $html = '<h1 id="tsinf_comfortdb_headline">' . $headline . '</h1>';
        
        $html .= '<a class="tsinf_comfortdb_back_to_last_button" href="' . admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s', $tablename)) . '"><span class="arrow-left white"></span><span>' . __('Back to Table', 'tsinf_comfortdb_plugin_textdomain') . '</span></a>'; 
        
        $html .= '<form method="post">';
        
        global $wpdb;
        
        if(TS_INF_COMFORT_DB_DATABASE::table_exists($tablename))
        {
            $nonce_key = '';
            
            $error_message = '';
            $result_array = array();
            
            // EDIT
            if(isset($_POST['tsinf_comfortdb_plugin_edit_dataset_submit']) && isset($_POST['tsinf_comfortdb_plugin_edit_dataset']))
            {
                $result_array = self::submit_dataset_input_form($_POST['tsinf_comfortdb_plugin_edit_dataset'], $identifiers);
            } else if(isset($_POST['tsinf_comfortdb_plugin_new_dataset_submit']) && isset($_POST['tsinf_comfortdb_plugin_edit_dataset']))
            {
                // NEW
                $result_array = self::submit_dataset_input_form($_POST['tsinf_comfortdb_plugin_edit_dataset']);
            }
            
            $sql_error_returned = (bool) false;
            if(isset($result_array['sql']))
            {
                $result_message_class = '';
                $result_error_message = '';
                $result_message_success_additional_information = '';
                if(isset($result_array['success']) && $result_array['success'] === true)
                {
                    $sql_error_returned = (bool) false;

                    $result_message_class = 'success';
                    $result_message_success_additional_information = '<span class="tsinf_comfortdb_plugin_result_additional_information">' . __('Affected Rows','tsinf_comfortdb_plugin_textdomain') . ': ' . $result_array['affected_rows'] . '</span>';
                } else {
                    $sql_error_returned = (bool) true;

                    $result_message_class = 'fail';
                    if(isset($result_array['error_message']) && strlen($result_array['error_message']) > 0)
                    {
                        $result_error_message = '<span class="tsinf_comfortdb_plugin_result_error_message">' . __('An error occured','tsinf_comfortdb_plugin_textdomain') . ': ' . $result_array['error_message'] . '</span>';
                    }
                }

                $error_message = '<div class="tsinf_comfortdb_plugin_result_message ' . $result_message_class . '">' . htmlspecialchars($result_array['sql']) . $result_error_message . $result_message_success_additional_information . '</div>';
            }
            
            $submit_button_name = '';
            
            $submit_button_label = __('Save Data', 'tsinf_comfortdb_plugin_textdomain');
            
            // EDIT
            if(is_array($identifiers) && count($identifiers) > 0)
            {
                $nonce_key = 'action_tsinf_comfortdb_plugin_edit_dataset_submit';

                $submit_button_name = 'tsinf_comfortdb_plugin_edit_dataset_submit';
                
                $html .= $error_message;

                $identifier_string = self::helper_decode_identifier_string($identifiers);
                
                $result = $wpdb->get_results("SELECT * FROM " . $tablename . " WHERE " . $identifier_string . " LIMIT 1;", ARRAY_A);
                
                $row_counter = 0;
                if(is_array($result) && count($result) > 0)
                {
                    foreach($result as $row)
                    {
                        if(is_array($row) && count($row) > 0)
                        {
                            foreach($row as $col_name => $col_val)
                            {
                                $row_counter++;
                            
                                $even_odd = ' odd ';
                                if($row_counter % 2 == 0)
                                {
                                    $even_odd = ' even ';
                                }
                                
                                $readonly = '';
                                $unlock = '';
                                
                                $field_meta = TS_INF_COMFORT_DB_DATABASE::get_column_metadata($tablename, $col_name);
                                
                                $primary_key_text = '';
                                if($field_meta->is_primary_key)
                                {
                                    $readonly = ' readonly="readonly" ';
                                    $unlock = '<span class="tsinf_comfortdb_unlock_item locked" data-lockedtxt="' . __('Unlock Key Field', 'tsinf_comfortdb_plugin_textdomain') . '" data-unlockedtxt="' . __('Lock Key Field', 'tsinf_comfortdb_plugin_textdomain') . '">' . __('Unlock Key Field', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                                    $primary_key_text = '<span class="label_meta label_primary_key">' . __('Primary Key', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                                }
                                
                                $auto_inc_text = '';
                                if($field_meta->auto_inc)
                                {
                                    $auto_inc_text = '<span class="label_meta label_autoinc">' . __('Auto Increment Column', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                                }
                                
                                $can_be_null_text = '';
                                $col_is_null_val = '';
                                if($field_meta->can_be_null)
                                {
                                    $can_be_null_text = '<span class="label_meta label_autoinc">' . __('NULL possible', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                                    $col_is_null_val =  "<input type='checkbox' class='tsinf_comfortdb_plugin_edit_dataset_is_null_checkbox' name='tsinf_comfortdb_plugin_edit_dataset[" . $col_name . "]' value='" . TSINF_COMFORT_DB_SET_COL_VALUE_NULL . "' /> <label for='tsinf_comfortdb_plugin_edit_dataset[" . $col_name . "]' class='tsinf_comfortdb_plugin_edit_dataset_is_null_label'>" . __('Value is set to NULL', 'tsinf_comfortdb_plugin_textdomain') . "</label>";
                                }
                                
                                $col_value = "";
                                if($sql_error_returned === true && isset($_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name]) && is_string($_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name]) && strlen($_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name]) > 0)
                                {
                                    $col_value = $_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name];
                                } else {
                                    $col_value = $col_val;
                                }

                                $render_val = '<textarea class="tsinf_comfortdb_plugin_edit_dataset_edit_field" ' . $readonly . ' name="tsinf_comfortdb_plugin_edit_dataset[' . $col_name . ']" id="" class="">' . $col_val . '</textarea>';
                                if(is_null($col_val))
                                {                                    
                                    $col_is_null_val =  "<input type='checkbox' checked='checked' class='tsinf_comfortdb_plugin_edit_dataset_is_null_checkbox' name='tsinf_comfortdb_plugin_edit_dataset[" . $col_name . "]' value='" . TSINF_COMFORT_DB_SET_COL_VALUE_NULL . "' /> <label for='tsinf_comfortdb_plugin_edit_dataset[" . $col_name . "]' class='tsinf_comfortdb_plugin_edit_dataset_is_null_label'>" . __('Value is set to NULL', 'tsinf_comfortdb_plugin_textdomain') . "</label>";
                                    $render_val = '<textarea class="tsinf_comfortdb_plugin_edit_dataset_edit_field" disabled="disabled" ' . $readonly . ' name="tsinf_comfortdb_plugin_edit_dataset[' . $col_name . ']" id="" class="">' . $col_val . '</textarea>';
                                }
                                
                                $html .= '<div class="tsinf_comfortdb_plugin_edit_dataset_row ' . $even_odd . '">
                                            <label class="tsinf_comfortdb_plugin_edit_dataset_label">' . $col_name . '
                                                <span class="label_meta_container">' . $primary_key_text . $auto_inc_text . $can_be_null_text . '</span>
                                            </label>
                                            ' . $render_val . '
                                            <span class="tsinf_comfortdb_plugin_edit_dataset_options">' . $unlock . $col_is_null_val . '</span>
                                        </div>';
                            }
                        } else {
                            // ERROR
                        }
                    }
                }
                
            } else {
                // NEW / SEARCH
                $search_form = (bool) false;
                if(strlen($action) > 0 && $action === 'search') {
                    // SEARCH
                    $nonce_key = 'action_tsinf_comfortdb_plugin_search_table_submit';
                    $submit_button_name = 'tsinf_comfortdb_plugin_search_table_submit';
                    $submit_button_label = __('Search Data', 'tsinf_comfortdb_plugin_textdomain');
                    $search_form = (bool) true;
                } else {
                    $nonce_key = 'action_tsinf_comfortdb_plugin_new_dataset_submit';
                    $submit_button_name = 'tsinf_comfortdb_plugin_new_dataset_submit';
                    $submit_button_label = __('Save Data', 'tsinf_comfortdb_plugin_textdomain');
                }
                
                $html .= $error_message;
                
                $column_names = TS_INF_COMFORT_DB_DATABASE::get_column_names($tablename);
                if(is_array($column_names) && count($column_names) > 0)
                {
                    $row_counter = 0;
                    foreach($column_names as $col_name)
                    {
                        $row_counter++;
                        
                        $even_odd = ' odd ';
                        if($row_counter % 2 == 0)
                        {
                            $even_odd = ' even ';
                        }
                        
                        $readonly = '';
                        $unlock = '';
                        
                        $field_meta = TS_INF_COMFORT_DB_DATABASE::get_column_metadata($tablename, $col_name);

                        $primary_key_text = '';
                        if($field_meta->is_primary_key)
                        {
                            $primary_key_text = '<span class="label_meta label_primary_key">' . __('Primary Key', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                        }
                        
                        $auto_inc_text = '';
                        if($field_meta->auto_inc && $search_form === false)
                        {
                            $readonly = ' readonly="readonly" ';
                            $unlock = '<span class="tsinf_comfortdb_unlock_item locked" data-lockedtxt="' . __('Unlock Auto Increment Field', 'tsinf_comfortdb_plugin_textdomain') . '" data-unlockedtxt="' . __('Lock Auto Increment Field', 'tsinf_comfortdb_plugin_textdomain') . '">' . __('Unlock Auto Increment Field', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                            $auto_inc_text = '<span class="label_meta label_autoinc">' . __('Auto Increment Column', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                        }
                        
                        $can_be_null_text = '';
                        $col_is_null_val = '';
                        if($field_meta->can_be_null)
                        {
                            $can_be_null_text = '<span class="label_meta label_autoinc">' . __('NULL possible', 'tsinf_comfortdb_plugin_textdomain') . '</span>';
                            $col_is_null_val =  "<input type='checkbox' class='tsinf_comfortdb_plugin_edit_dataset_is_null_checkbox' name='tsinf_comfortdb_plugin_edit_dataset[" . $col_name . "]' value='" . TSINF_COMFORT_DB_SET_COL_VALUE_NULL . "' /> <label for='tsinf_comfortdb_plugin_edit_dataset[" . $col_name . "]' class='tsinf_comfortdb_plugin_edit_dataset_is_null_label'>" . __('Value is set to NULL', 'tsinf_comfortdb_plugin_textdomain') . "</label>";
                        }
                        
                        $col_value = "";
                        if($sql_error_returned === true && isset($_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name]) && is_string($_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name]) && strlen($_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name]) > 0)
                        {
                            $col_value = $_POST['tsinf_comfortdb_plugin_edit_dataset'][$col_name];
                        }
                        
                        $render_val = '<textarea class="tsinf_comfortdb_plugin_edit_dataset_edit_field" ' . $readonly . ' name="tsinf_comfortdb_plugin_edit_dataset[' . $col_name . ']" id="" class="">' . $col_value . '</textarea>';
                        
                        $html .= '<div class="tsinf_comfortdb_plugin_edit_dataset_row ' . $even_odd . '">
                                    <label class="tsinf_comfortdb_plugin_edit_dataset_label">' . $col_name . '
                                        <span class="label_meta_container">' . $primary_key_text . $auto_inc_text . $can_be_null_text . '</span>
                                    </label>
                                    ' . $render_val . '
                                    <span class="tsinf_comfortdb_plugin_edit_dataset_options">' . $unlock . $col_is_null_val . '</span>
                                </div>';
                    }
                    
                   
                    
                }
            }
            
            
            $html .= wp_nonce_field($nonce_key, '_wpnonce', true, false);
            
            $html .= '<input type="hidden" name="tsinf_comfortdb_plugin_edit_dataset[tsinf_comfortdb_plugin_edit_dataset_table]" value="' . $tablename . '" />';
            $html .= '<div class="tsinf_comfort_db_edit_submit_button_container">' . get_submit_button($submit_button_label, 'primary', $submit_button_name) . '</div>';
        } else {
            // ERROR
        }
        
        $html .= '</form>';
        echo $html;
    }
    
    /**
	 * Creates an URL-Parameter List of an identifier array
	 * @param array $identifiers Array with identifier parameters which identifies the dataset (Primary Keys [keyname] => [keyvalue])
	 * @return string concatenate url parameters
	 */
    private static function helper_decode_identifier_string($identifiers)
    {
        $identifier_string = '';
        $identifiers_length = count($identifiers);
        $identifiers_counter = 0;
        if(is_array($identifiers) && $identifiers_length > 0)
        {
            foreach($identifiers as $identifier)
            {
            	$identifiers_counter++;
                if($identifiers_length === $identifiers_counter)
                {
                    $identifier_string .= " " . stripslashes(urldecode($identifier)) . " ";
                } else {
                    $identifier_string .= " " . stripslashes(urldecode($identifier)) . " AND "; 
                }
            }
                                
            
            $identifier_string = str_replace("{percent}", "%%", $identifier_string);

        }
        
        return $identifier_string;
        
    }
    
    /**
	 * Processes data which are sent from new/edit dataset form (rendered by method render_dataset_input_form())
	 * @param array $post_array
	 * @param array $identifiers
	 * @return array Result Array with Informations 
	 * (['sql'] => Executed SQL Statement (string),  
	 * ['success'] => true if execution was successful/otherwise false (bool), 
	 * ['affected_rows'] => Rows affcted by execution (int), 
	 * ['error_message'] => If available error message on error)
	 */      
    private static function submit_dataset_input_form($post_array, $identifiers = array(), $action = '')
    {
        $table = htmlentities(strip_tags($post_array['tsinf_comfortdb_plugin_edit_dataset_table']), ENT_QUOTES);
        
        $return_array = array('sql' => '', 'success' => false, 'error_message' => '', 'affected_rows' => '');
        
        $action = trim($action);
        
        $search_data = (bool) false;
        if($action === 'search')
        {
            $search_data = (bool) true;
        }
            
        global $wpdb;
        
        // EDIT
        if(is_array($identifiers) && count($identifiers) > 0 && $search_data === false)
        {
            if(check_admin_referer( 'action_tsinf_comfortdb_plugin_edit_dataset_submit', '_wpnonce' ))
            {
                $sql = "UPDATE `" . $table . "` SET ";

                $set_array = array();
                if(is_array($post_array) && count($post_array) > 1)
                {
                    foreach($post_array as $field => $value)
                    {
                        if($field === 'tsinf_comfortdb_plugin_edit_dataset_table')
                        {
                            continue;
                        }
                        
                        $filtered_value = $value;
                        if($value === TSINF_COMFORT_DB_SET_COL_VALUE_NULL)
                        {
                            $set_array[] = " `" . $field . "`=NULL ";
                        } else {
                            $set_array[] = " `" . $field . "`='" . $filtered_value . "' ";
                        }
                    }

                    $sql .= implode(",", $set_array);
                }

                $identifier_string = self::helper_decode_identifier_string($identifiers);

                $sql .= " WHERE " . $identifier_string . " LIMIT 1;";

                $return_array['sql'] = $sql;

                $wpdb->query("START TRANSACTION");
                $return = $wpdb->query($sql);
                if(is_wp_error($return))
                {
                    $return_array['error_message'] = $return->get_error_message();
                } else if($wpdb->last_error)
                {
                    $return_array['error_message'] = $wpdb->last_error;
                }

                if($return !== FALSE)
                {
                    $wpdb->query("COMMIT");
                    $return_array['success'] = (bool) true;
                    $return_array['affected_rows'] = $return;

                } else {
                    $wpdb->query("ROLLBACK");
                    $return_array['success'] = (bool) false;
                }
            }
        } else if($search_data === true) {
            // SEARCH
            $search_results = array();
            
            if(check_admin_referer( 'action_tsinf_comfortdb_plugin_search_table_submit', '_wpnonce' ))
            {
                $values = array();
                
                if(is_array($post_array) && count($post_array) > 0)
                {
                    foreach($post_array as $key => $value)
                    {
                        if($key === 'tsinf_comfortdb_plugin_edit_dataset_table')
                        {
                            continue;
                        }
                        
                        $cur_key = sanitize_text_field($key);
                        $cur_val = sanitize_text_field($value);
                        
                        if(strlen($cur_key) > 0 && strlen($cur_val) > 0)
                        {
                            $values[$cur_key] = $cur_val;
                        }
                        
                        
                    }
                }
                
                if(is_array($values) && count($values) > 0 && isset($post_array['tsinf_comfortdb_plugin_edit_dataset_table']) && strlen($post_array['tsinf_comfortdb_plugin_edit_dataset_table']) > 0)
                {
                    $tablename = sanitize_text_field($post_array['tsinf_comfortdb_plugin_edit_dataset_table']);
                    
                    if(strlen($tablename) > 0)
                    {
                        $search_identifiers = self::helper_get_search_identifier_parameter($tablename, $values);
                    }
                }
            }
            
        } else {
            // NEW
            if(check_admin_referer( 'action_tsinf_comfortdb_plugin_new_dataset_submit', '_wpnonce' ))
            {
                if(is_array($post_array) && count($post_array) > 1)
                {
                    $sql = "INSERT INTO `" . $table . "`";
                    $col_array = array();
                    $value_array = array();

                    foreach($post_array as $field => $value)
                    {
                        if($field === 'tsinf_comfortdb_plugin_edit_dataset_table')
                        {
                            continue;
                        }
                        
                        $filtered_value = $value;
                                                
                        $col_array[] = $field;
                        
                        $filtered_value = $value;
                        if($value === TSINF_COMFORT_DB_SET_COL_VALUE_NULL)
                        {
                            $value_array[] = "NULL";
                        } else {
                            $value_array[] = "'" . $filtered_value . "'";
                        }
                    }

                    $sql .= '(';
                    $sql .= implode(",", $col_array);
                    $sql .= ') VALUES (';
                    $sql .= implode(",", $value_array);
                    $sql .= ');';

                    $return_array['sql'] = $sql;

                    $wpdb->query("START TRANSACTION");
                    
                    $return = $wpdb->query($sql);
                    if(is_wp_error($return))
                    {
                        $return_array['error_message'] = $return->get_error_message();
                    } else if($wpdb->last_error)
                    {
                        $return_array['error_message'] = $wpdb->last_error;
                    }

                    if($return !== FALSE)
                    {
                        $wpdb->query("COMMIT");
                        $return_array['success'] = (bool) true;
                        $return_array['affected_rows'] = $return;
                    } else {

                        $wpdb->query("ROLLBACK");
                        $return_array['success'] = (bool) false;                        
                    }
                }
            }
        }
                
        return $return_array;
    }
	
    /**
	 * Render Table Data View
	 * @param string Table to show
	 */
	private static function render_table_dataview($tablename)
	{
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $page = -1;
        if(isset($_GET['tpage']))
        {
            $page = (int) $_GET['tpage'];
        }
        
        if($page < 1)
        {
            $page = 1;
        }
        
        $param_order_by = null;
        $param_order = null;
        if(isset($_GET['orderby']) && isset($_GET['order']) && ($_GET['order'] === 'asc' || $_GET['order'] === 'desc')) {
            $param_order_by = urldecode($_GET['orderby']);
            $param_order_by = htmlentities(strip_tags($param_order_by), ENT_QUOTES);
            $param_order = urldecode($_GET['order']);
            $param_order = htmlentities(strip_tags($param_order), ENT_QUOTES);
        }
        ?>
		<?php
        $where = '';
        $search_active = (bool) false;
        
        // SEARCH
        if(isset( $_GET['page'] ) && 
            isset( $_GET['table'] ) && 
            isset( $_GET['action'] ) && 
            $_GET['action'] === 'search' &&
            isset( $_GET['identifier']))
        {
            $identifier = strip_tags(htmlspecialchars($_GET['identifier']));
            $identifier_array = explode("AND", $identifier);
            $where = self::helper_decode_identifier_string($identifier_array);
            $search_active = (bool) true;
        }
        
        $table_data = TS_INF_COMFORT_DB_DATABASE::get_table_data($tablename, self::$query_limit, $page, $param_order_by, $param_order, $where);
        
        $table_data_count = TS_INF_COMFORT_DB_DATABASE::get_total_table_count($tablename, $where);
        
        $table_headers = TS_INF_COMFORT_DB_DATABASE::get_column_names($tablename);
        $table_col_count = is_array($table_headers) ? count($table_headers) : 0;
        
        $primary_key_columns = TS_INF_COMFORT_DB_DATABASE::get_primary_key_columns($tablename);
        
        $no_primary_keys = (bool) false;
        if(is_array($primary_key_columns) === false || (is_array($primary_key_columns) && count($primary_key_columns) < 1))
        {
            $no_primary_keys = (bool) true;
        }
        
        $backlink = admin_url('?page=tscomfortdb-mainpage');
        $backlinktext = __('Back to Table-Overview', 'tsinf_comfortdb_plugin_textdomain');
        if($search_active === true)
        {
            $backlink = admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s&action=search', $tablename));
            $backlinktext = __('Back to Search Form', 'tsinf_comfortdb_plugin_textdomain');
        }
		?>
        <div id="tsinf_comfortdb_table_header_elements" class="">
            <h1 id="tsinf_comfortdb_headline">
                <?php _e("Table", "tsinf_comfortdb_plugin_textdomain"); ?>: 
                <?php echo $tablename; ?>
                &nbsp;|&nbsp;
                <?php echo sprintf(__("%d Entries", "tsinf_comfortdb_plugin_textdomain"), $table_data_count); ?>
                &nbsp;|&nbsp;
                <?php echo sprintf(__("%d Rows per Page", "tsinf_comfortdb_plugin_textdomain"), self::$query_limit); ?>
            </h1>
            <a class="tsinf_comfortdb_back_to_last_button" href="<?php echo $backlink; ?>">
                <span class="arrow-left white"></span>
                <span><?php echo $backlinktext; ?></span>
            </a>
            <?php if($no_primary_keys === true) { ?>
            <div id="tsinf_comfortdb_read_only_box"><?php _e("No identifier in this table found - Read only mode", "tsinf_comfortdb_plugin_textdomain"); ?></div>
            <?php } ?>
            <div class="tsinf_comfortdb_toolbar tabledata">
                <span id="select_columns" class="tsinf_select_menu" title="<?php _e("Turn columns off and on in current view", "tsinf_comfortdb_plugin_textdomain"); ?>"><span class="tsinf_select_menu_label_wrapper"><?php _e("Columns", "tsinf_comfortdb_plugin_textdomain"); ?>
                    <span class="tsinf_arrow"><span class="arrow-down white"></span></span></span>
                    <span class="tsinf_select_menu_options_wrap">
                        <span class="arrow-up white"></span>
                        <span class="tsinf_select_menu_items">
                        <?php 
                        if(is_array($table_headers) && count($table_headers) > 0)
                        {
                            foreach($table_headers as $column_name)
                            {
                                ?><span class="tsinf_select_menu_option" data-colname="<?php echo $column_name; ?>"><input type="checkbox" checked="checked" value="1" /><?php echo $column_name; ?></span><?php
                            }
                        }
                        ?>
                        </span>
                    </span>
                </span>
                <?php if(!$no_primary_keys) { ?>
                <span id="select_action" class="tsinf_select_menu" title="<?php _e("Select an action", "tsinf_comfortdb_plugin_textdomain"); ?>"><span class="tsinf_select_menu_label_wrapper"><?php _e("Actions", "tsinf_comfortdb_plugin_textdomain"); ?>
                    <span class="tsinf_arrow"><span class="arrow-down white"></span></span></span>
                    <span class="tsinf_select_menu_options_wrap">
                        <span class="arrow-up white"></span>
                        <span class="tsinf_select_menu_items">
                            <span class="tsinf_select_menu_option delete" data-auth="<?php echo wp_create_nonce('tsinf_comfortdb_plugin_delete_table_dataset'); ?>"><?php _e("Delete selected rows", "tsinf_comfortdb_plugin_textdomain"); ?></span>
                            <span class="tsinf_select_menu_option export_csv" data-auth="<?php echo wp_create_nonce('tsinf_comfortdb_plugin_export_csv_table_dataset'); ?>"><?php _e("Export selected rows to CSV", "tsinf_comfortdb_plugin_textdomain"); ?></span>
                        </span>
                    </span>
                </span>
                <?php } ?>
                <span id="select_page" class="tsinf_select_menu" title="<?php _e("Switch to another page", "tsinf_comfortdb_plugin_textdomain"); ?>"><span class="tsinf_select_menu_label_wrapper"><?php echo sprintf(__("Page: %s", "tsinf_comfortdb_plugin_textdomain"), $page); ?>
                    <span class="tsinf_arrow"><span class="arrow-down white"></span></span></span>
                    <span class="tsinf_select_menu_options_wrap">
                        <span class="arrow-up white"></span>
                        <span class="tsinf_select_menu_items">
                        <?php $pagination_prefix_page = __('Page', "tsinf_comfortdb_plugin_textdomain") . ": "; ?>
                        <?php self::render_pagination($tablename, self::$query_limit, $page, $table_data_count, 'numbers', $pagination_prefix_page, $search_active); ?>
                        </span>
                    </span>
                </span>
                <span id="switch_page"><?php self::render_pagination($tablename, self::$query_limit, $page, $table_data_count, 'arrows', '', $search_active); ?></span>
                <span id="switch_table" class="tsinf_select_menu" title="<?php _e("Switch to another table", "tsinf_comfortdb_plugin_textdomain"); ?>"><span class="tsinf_select_menu_label_wrapper"><?php echo sprintf(__("Table: %s", "tsinf_comfortdb_plugin_textdomain"), $tablename); ?>
                    <span class="tsinf_arrow"><span class="arrow-down white"></span></span></span>
                    <span class="tsinf_select_menu_options_wrap">
                        <span class="arrow-up white"></span>
                        <span class="tsinf_select_menu_items">
                        <?php 
                        $tables = TS_INF_COMFORT_DB_DATABASE::get_tables();
                        if(is_array($tables) && count($tables) > 0)
                        {
                            foreach($tables as $table)
                            {
                                ?><a href="<?php echo admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s', $table->TABLE_NAME)); ?>" class="tsinf_select_menu_option"><?php echo $table->TABLE_NAME; ?></a><?php
                            }
                        }
                        ?>
                        </span>
                    </span>
                </span>
                <a class="tsinf_symbol_button plus-symbol table_new_dataset" href="<?php echo admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s&action=new', $tablename)) ?>" title="<?php _e("Create a new dataset in table", "tsinf_comfortdb_plugin_textdomain"); ?>"></a>
                <input id="tsinf_comfortdb_table_filter" title="<?php _e("Filter table content", "tsinf_comfortdb_plugin_textdomain"); ?>" placeholder="Filter" type="text" value="" />
                <a id="search_table" class="tsinf_symbol_button" href="<?php echo admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s&action=search', $tablename)); ?>"><?php _e('Search', 'tsinf_comfortdb_plugin_textdomain'); ?></a>
            </div>
            <div class="tsinf_comfort_db_header_mobile_switch"><span class="arrow-up"></span></div>
        </div>
        
		<div class="tsinf_comfortdb_table_data_wrapper">
			<table class="tsinf_comfortdb_table <?php echo (self::$skin === "wordpress") ? "wp-list-table widefat striped table-view-list" : ""; ?>" data-tablename="<?php echo $tablename; ?>" data-colcount="<?php echo $table_col_count; ?>">
			<?php 
			$sort_link_order = 'asc';
            if(isset($_GET['order']) && $_GET['order'] === 'asc')
            {
                $sort_link_order = 'desc';
            }
        
            if(is_array($table_headers) && $table_col_count > 0)
			{
				$column_headline = '';
				$column_meta_data = '';
				foreach($table_headers as $column_name)
				{
					$sort_link_search_params = '';
					if(isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['identifier']))
					{
					    $identifier = strip_tags(htmlspecialchars($_GET['identifier']));
					    $sort_link_search_params = sprintf('&action=search&identifier=%s', urlencode(stripslashes($identifier)));
					}
					
					$column_headline_sort_link = admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s&orderby=%s&order=%s&tpage=%d%s', urlencode($tablename), urlencode($column_name), urlencode($sort_link_order), urlencode($page), $sort_link_search_params));
                    
                    $column_orderby_class = '';
                    if($column_name === $param_order_by)
                    {
                        $column_orderby_class = ' active ';
                    }
                    
                    $col_nonce = wp_create_nonce('tsinf_comfortdb_plugin_get_meta_info_' . $column_name);
                    
                    $arrow_class = (self::$skin === "wordpress") ? "black" : "white";
                    
					$column_headline .= '<th class="tsinf_comfortdb_column_headline" data-column-name="' . $column_name . '"><a href="' . $column_headline_sort_link . '" class="' . $column_orderby_class. '">' . $column_name . ' 
                                            <span class="tsinf_arrow arrow-down ' . $arrow_class . '"></span></a>
                                        </th>';
					$column_meta_data .= '<th class="tsinf_comfortdb_column_metadata data" data-column-name="' . $column_name . '" data-metacol="' . $column_name . '" data-metacol-auth="' . $col_nonce . '"><img alt="" src="' . plugins_url('../images/loading-symbol.svg', __FILE__) . '" width="30px" height="auto" /></th>';
				}				
				?>
				<thead>
				<tr>
                    <?php if(!$no_primary_keys) { ?>
					<th class="tsinf_comfortdb_column_headline"><input type="checkbox" class="row_select_all" /></th>
					<th class="tsinf_comfortdb_column_headline"></th>
                    <?php } ?>
					<?php echo $column_headline; ?>
				</tr>
				<tr>
                    <?php if(!$no_primary_keys) { ?>
					<th colspan="2" class="tsinf_comfortdb_column_metadata"></th>
                    <?php } ?>
					<?php echo $column_meta_data; ?>
				</tr>
				</thead><?php
			}
			
            if(is_array($table_data) && count($table_data) > 0)
		  {
			?>
			<tbody><?php
                
			$row_counter = 0;
                
        
			foreach($table_data as $table_dataset)
			{
			    $row_counter++;
                $edit_link = self::helper_get_row_edit_link($tablename, $table_dataset);
                $row_identifier = self::helper_get_row_identifier($tablename, $table_dataset);
                
                ?><tr data-page-line="<?php echo $row_counter; ?>">
                    <?php if(!$no_primary_keys) { ?>
					<td class="cell_checkbox"><input type="checkbox" class="row_select row_identifier" name="row_identifier" value="<?php echo htmlentities($row_identifier, ENT_QUOTES); ?>" /></td>
					<td class="cell_link"><a class="tsinf_comfortdb_row_edit" href="<?php echo admin_url('?page=tscomfortdb-mainpage&table=' . $tablename . $edit_link); ?>"><?php _e('Edit', 'tsinf_comfortdb_plugin_textdomain'); ?></a></td>
                <?php } ?>
				<?php
				
				
				$column_counter = 0;
                
				foreach($table_dataset as $column_name => $column_data)
				{	
					$column_counter++;
					
					$col_data_is_null = (bool) false;
					$col_data_is_null_class = "";
					if(is_null($column_data)) {
					    $col_data_is_null = (bool) true;
					    $col_data_is_null_class = " col_data_is_null ";
					}
					?>
					<td class="<?php echo $col_data_is_null_class; ?>" data-column-name="<?php echo $column_name ?>">
					<?php if($col_data_is_null) { ?>
					<span class="column_is_null">NULL</span>
					<?php } else { ?>
					<textarea class="dbcontentcodewrap" readonly="readonly"><?php echo htmlspecialchars($column_data); ?></textarea>
					<span class="tcellmenubutton"></span>
					<span class="tcellmenu_container">
						<span class="multiplestate_header">
                        	<span class="header_text"><?php _e('Cell-Options', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
                            <span class="menu_close">
                                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                        	</span>
	                 	</span>
                    	<span class="tcellmenu_option" data-val="copy">
                			<span class="option_text"><?php _e('Copy to clipboard', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
            			</span>
            			<span class="tcellmenu_option" data-val="unserialize">
                			<span class="option_text"><?php _e('PHP: unserialize()', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
            			</span>
            			<span class="tcellmenu_option" data-val="jsondecode">
                			<span class="option_text"><?php _e('PHP: json_decode()', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
            			</span>
            			<span class="tcellmenu_option" data-val="varexport">
                			<span class="option_text"><?php _e('PHP: var_export()', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
            			</span>
        			</span>
					<?php
					}
					?>
					</td>
					<?php 
				}
                

				?></tr><?php
			}
			?>
			</tbody>
			
		<?php
		}
        ?>
            </table>
		</div>
        <?php
	}
	
    /**
	 * Render Table Overview
	 */
    private static function render_table_overview()
	{
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
		
		$database_size = TS_INF_COMFORT_DB_DATABASE::get_used_space_database();
		$mysql_version = TS_INF_COMFORT_DB_DATABASE::get_mysql_version();
		$php_version = phpversion();
		
		$disable_overview_meta = ((int) get_option('tscdb_disable_table_overview_meta') === 1);
		
		global $wp_version;
		
		$headline = __('TS Comfort DB', 'tsinf_comfortdb_plugin_textdomain');
		
		try {
	       $plugin_data = get_plugin_data(TS_INF_COMFORT_DB_MAIN_FILE);
	       $headline = $plugin_data['Name'] . " v. " . $plugin_data['Version'];
	       
		} catch(Exception $e)
		{
		    $headline = __('TS Comfort DB', 'tsinf_comfortdb_plugin_textdomain');
		}
        ?>
        <h1 id="tsinf_comfortdb_headline" class="nofloat"><?php echo $headline; ?></h1>
        <h2><?php echo sprintf(__('Name of your WordPress Database: %s (%s MB) - MySQL Version %s - PHP Version %s - WordPress Version %s', 'tsinf_comfortdb_plugin_textdomain'), DB_NAME, number_format($database_size, 2, ".", ""), $mysql_version, $php_version, $wp_version); ?></h2>
        <?php self::render_full_text_search_form(); ?>
        <?php
        $tables = TS_INF_COMFORT_DB_DATABASE::get_tables();
        if(is_array($tables) && count($tables))
        {
            ?>
            <div class="tsinf_comfortdb_table_data_wrapper">
                <table id="tsinf_comfortdb_table_overview" class="tsinf_comfortdb_table <?php echo $disable_overview_meta ? "disable_loading_meta" : ""; ?> <?php echo (self::$skin === "wordpress") ? "wp-list-table widefat striped table-view-list" : ""; ?>">
                     <thead>
                        <tr>
                            <th class="tsinf_comfortdb_column_headline"><?php _e('Table Name', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
                            <th class="tsinf_comfortdb_column_headline tsinf_comfortdb_colhead_tablename_filter"><input type="text" class="tsinf_comfortdb_tablename_filter" placeholder="<?php _e('Filter', 'tsinf_comfortdb_plugin_textdomain'); ?> (<?php _e('Table Name', 'tsinf_comfortdb_plugin_textdomain'); ?>)" value="" /></th>
                            <th class="tsinf_comfortdb_column_headline tsinf_comfortdb_colhead_options"></th>
                            <th class="tsinf_comfortdb_column_headline tsinf_comfortdb_colhead_entries"><?php _e('Entries', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
                            <th class="tsinf_comfortdb_column_headline tsinf_comfortdb_colhead_engine"><?php _e('Engine', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
                            <th class="tsinf_comfortdb_column_headline tsinf_comfortdb_colhead_size"><?php _e('Size', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
                        </tr>
                    </thead>
                <?php
                foreach($tables as $table)
                {
                    ?>
                    <tr class="tsinf_comfortdb_table_overview_row" data-table-name="<?php echo $table->TABLE_NAME; ?>" data-auth="<?php echo wp_create_nonce(sprintf('tsinf_comfortdb_table_overview_row_%s', $table->TABLE_NAME)); ?>">
                        <td colspan="2" class="tsinf_comfortdb_table_name"><a href="<?php echo admin_url('?page=tscomfortdb-mainpage&table=' . $table->TABLE_NAME . '&tpage=1'); ?>" title="<?php echo $table->TABLE_NAME; ?>"><?php echo $table->TABLE_NAME; ?></a></td>
                        <td class="tsinf_comfortdb_options">
                        	<span class="export_to_csv_wrap">
                        		<?php if($table->TABLE_ROWS > 0) { ?>
                        		<span class="export_to_csv"><?php _e('CSV-Export', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
                        		<progress class="tsinf-progress-bar export-complete-table-to-csv" style="display:none;" value="0.0" max="1"></progress>
                        		<?php } ?>
                        	</span>
                        </td>
                        <td class="tsinf_comfortdb_row_count"><?php echo $table->TABLE_ROWS; ?></td>
                        <td class="tsinf_comfortdb_engine">
                        	<?php if($disable_overview_meta === true) { ?>
                        	<span class="tsinf_loadmeta_link"><?php _e('Click to load', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
                        	<?php } else { ?>
                        	<img alt="" src="<?php echo plugins_url('../images/loading-symbol.svg', __FILE__); ?>" width="30px" height="auto" />
                        	<?php } ?>
                        </td>
                        <td class="tsinf_comfortdb_used_space">
                        	<?php if($disable_overview_meta === true) { ?>
                        	<span class="tsinf_loadmeta_link"><?php _e('Click to load', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
                        	<?php } else { ?>
                        	<img alt="" src="<?php echo plugins_url('../images/loading-symbol.svg', __FILE__); ?>" width="30px" height="auto" />
                        	<?php } ?>
                    	</td>
                    </tr>
                    <?php
                }
                ?>
                </table>
            </div>
            <?php
        }
	}
    
    /**
	 * Render Full Text Search Form
	 */
    private static function render_full_text_search_form()
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $search_value = self::helper_get_full_text_search_value();
        ?>
        <form method="get" action="<?php echo admin_url('?page=tscomfortdb-mainpage'); ?>">
                <input type="hidden" name="page" value="tscomfortdb-mainpage" />
                <input class="tsinf_comfortdb_full_text_search" name="tsinf_comfortdb_full_text_search" id="tsinf_comfortdb_full_text_search" type="text" value="<?php echo $search_value; ?>" placeholder="<?php echo _e('Full Text Search', 'tsinf_comfortdb_plugin_textdomain'); ?>" />
                <?php 
                wp_nonce_field('action_tsinf_comfortdb_plugin_full_text_search_submit', '_wpnonce', true, true);
                ?>
        </form>
        <?php
    }
    
    /**
	 * Render Full Text Search Result Overview: Tables and Number of Matches in Table
	 */
    private static function render_full_text_search()
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $search_value = self::helper_get_full_text_search_value();
        ?>
        <h1 id="tsinf_comfortdb_headline"><?php _e('Full Text Search', 'tsinf_comfortdb_plugin_textdomain') ?></h1>
        <a class="tsinf_comfortdb_back_to_last_button" href="<?php echo admin_url('?page=tscomfortdb-mainpage'); ?>">
            <span class="arrow-left white"></span>
            <span><?php _e('Back to Table-Overview', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
        </a>
        <?php
        self::render_full_text_search_form();
        if(strlen($search_value) > 0)
        {
            $result_tables = TS_INF_COMFORT_DB_DATABASE::do_full_text_search($search_value);
            ?>
            <h2><?php _e('Search Results in following tables', 'tsinf_comfortdb_plugin_textdomain'); ?></h2>
            <div class="tsinf_comfortdb_table_data_wrapper">
            <?php
            if(is_array($result_tables) && count($result_tables) > 0)
            {
                ?><table class="tsinf_comfortdb_table <?php echo (self::$skin === "wordpress") ? "wp-list-table widefat striped table-view-list" : ""; ?>">
                    <thead>
                        <tr>
                            <th class="tsinf_comfortdb_column_headline"><?php _e('Table Name', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
                            <th class="tsinf_comfortdb_column_headline"><?php _e('Search Result Count', 'tsinf_comfortdb_plugin_textdomain'); ?></th>
                        </tr>
                    </thead>
                <?php
                foreach($result_tables as $tablename => $result_count)
                {
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url(sprintf('?page=tscomfortdb-mainpage&tsinf_comfortdb_full_text_search=%s&table_identifier=%s',$search_value,$tablename)); ?>">
                                <span class="tsinf_comfortdb_table_fts_result_table_row_name"><?php echo $tablename; ?></span>
                            </a>
                        </td>
                        <td>
                            <span class="tsinf_comfortdb_table_fts_result_table_row_count"><?php echo $result_count; ?></span>
                        </td>
                        
                    </tr>
                    <?php
                }?>
                </table>
                <?php
            }
            ?>
            </div>
            <?php
        }
    }
    
    /**
	 * Render Full Text Search Results for Table
	 */
    private static function render_full_text_search_table()
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $search_value = self::helper_get_full_text_search_value();
        $tablename = htmlentities(strip_tags($_GET['table_identifier']), ENT_QUOTES);
        ?>
        <h1 id="tsinf_comfortdb_headline"><?php echo sprintf(__('Full Text Search: %s in Table %s', 'tsinf_comfortdb_plugin_textdomain'), $search_value, $tablename) ?></h1>
        <?php
        $results = TS_INF_COMFORT_DB_DATABASE::do_full_text_search_in_table($search_value, $tablename);
        $overview_nonce = wp_create_nonce('action_tsinf_comfortdb_plugin_full_text_search_submit');
        ?>
        <a class="tsinf_comfortdb_back_to_last_button" href="<?php echo admin_url(sprintf('?page=tscomfortdb-mainpage&tsinf_comfortdb_full_text_search=%s&_wpnonce=%s',$search_value,$overview_nonce)); ?>">
            <span class="arrow-left white"></span>
            <span><?php _e('Back to Overview', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
        </a>
        <div class="tsinf_comfortdb_table_data_wrapper">
        <?php
        if(is_array($results) && count($results) > 0)
        {
            $table_headers = TS_INF_COMFORT_DB_DATABASE::get_column_names($tablename);
            ?>
            <table class="tsinf_comfortdb_table <?php echo (self::$skin === "wordpress") ? "wp-list-table widefat striped table-view-list" : ""; ?>">
            <?php
            if(is_array($table_headers) && count($table_headers) > 0)
            {
                if(is_array($table_headers) && count($table_headers) > 0)
                {
                    ?><thead><tr><?php
                    foreach($table_headers as $column_name)
                    {
                        ?><th class="tsinf_comfortdb_column_headline"><?php echo $column_name; ?></th><?php
                    }
                    ?>
                    </tr></thead>
                    <?php
                }
            }
            
            foreach($results as $result_row)
            {
                ?><tr>
                <?php
                $columns = (array) $result_row;
                foreach($columns as $col)
                {
                    ?><td><textarea class="dbcontentcodewrap" readonly="readonly"><?php echo $col; ?></textarea></td><?php
                }
                ?>
                </tr><?php
            }
            ?>
            </table>
            <?php
        }
        ?>
        </div>
        <?php
    }
    
    /**
	 * Render Pagenation for Table Data View (renderd by method render_table_dataview)
	 * @param string $tablename Table Name
	 * @param integer $limit Rows per Site
	 * @param integer $page Current Page
	 * @param integer $total Total Number of rows
	 * @param string $mode all | numbers | arrows
	 * all => Render Pagination with numbers and arrows
	 * numbers => Render Pagination with numbers
	 * arrows => Render Pagination with arrows
	 */
    private static function render_pagination($tablename, $limit, $page, $total, $mode = 'all', $number_prefix = '', $search = false)
    {
        if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tsinf_comfortdb_plugin_textdomain' ) );
		}
        
        $limit = self::$query_limit;
        
        $links_to_show = 5;
        $last       = ceil($total/$limit);
        
        $previous = $page - 1;
        $previous_class = '';
        if($page === 1)
        {
            $previous = $page;
            $previous_class = 'disabled';
        }
        
        $next = $page + 1;
        $next_class = '';
        if($next > $last)
        {
            $next = $last;
            $next_class = 'disabled';
        }
                
        $order_by_params = '';
        if(isset($_GET['orderby']))
        {
            $order_by_param = '';
            $order_param = '';
            
            $order_by_val = htmlentities(strip_tags($_GET['orderby']), ENT_QUOTES);
            $order_by_param = '&orderby=' . $order_by_val;
            
            if(isset($_GET['order']))
            {
                $order_by_val = htmlentities(strip_tags($_GET['order']), ENT_QUOTES);
                $order_param = '&order=' . $order_by_val;
            }
            
            $order_by_params = $order_by_param . $order_param;
        }
     
        $html = '';
        
        $baselink = '?page=tscomfortdb-mainpage&table=' . $tablename;
        if($search === true)
        {
            $identifier = strip_tags(htmlspecialchars($_GET['identifier']));
            $sort_link_search_params = sprintf('&action=search&identifier=%s', urlencode(stripslashes($identifier)));
            $baselink = '?page=tscomfortdb-mainpage&table=' . $tablename . $sort_link_search_params;
            
            
        }
        
        switch($mode)
        {
            case 'all':
                {
                    $html .= '<a title="' . __("Switch to previous page", "tsinf_comfortdb_plugin_textdomain") . '" class="tsinf_select_menu_option pagination_arrow ' . $previous_class . '" href="?page=tscomfortdb-mainpage&table=' . $tablename . '&tpage=' . $previous . $order_by_params . '">&laquo;</a>';
                    for ( $i = 1 ; $i <= $last; $i++ ) {
                        $class  = ( $page == $i ) ? "active" : "";
                        $html .= '<a class="tsinf_select_menu_option pagination_number ' . $class . '" href="' . $baselink .'&tpage=' . $i . $order_by_params . '">' . $i . '</a>';
                    }
                    $html .= '<a title="' . __("Switch to next page", "tsinf_comfortdb_plugin_textdomain") . '" class="tsinf_select_menu_option pagination_arrow ' . $next_class . '" href="' . $baselink .'&tpage=' . ( $page + 1 ) . $order_by_params . '">&raquo;</a>';
                    break;
                }
            
            case 'numbers':
                {               
                    for ( $i = 1 ; $i <= $last; $i++ ) {
                        $class  = ( $page == $i ) ? "active" : "";
                        $html .= '<a class="tsinf_select_menu_option pagination_number ' . $class . '" href="' . $baselink .'&tpage=' . $i . $order_by_params . '">' . $number_prefix . $i . '</a>';
                    }
                    break;
                }
                
            case 'arrows':
                {
                    $html .= '<a title="' . __("Switch to previous page", "tsinf_comfortdb_plugin_textdomain") . '" class="tsinf_select_menu_option pagination_arrow ' . $previous_class . '" href="' . $baselink .'&tpage=' . $previous . $order_by_params . '">
                                    <span class="arrow-left white"></span>
                                </a>';
                    $html .= '<a title="' . __("Switch to next page", "tsinf_comfortdb_plugin_textdomain") . '" class="tsinf_select_menu_option pagination_arrow ' . $next_class . '" href="' . $baselink .'&tpage=' . ( $page + 1 ) . $order_by_params . '">
                                    <span class="arrow-right white"></span>
                                </a>';
                    break;
                }
        }
        

        echo $html;
    }
	
    /**
	 * Extract Table Headers in an array
	 * @param object $dataset object with a dateset [fieldname] => [fieldvalue]
	 * @return array Table Headers
	 */
	private static function helper_extract_table_headers($dataset)
	{
		$table_headers = array();
		if(is_object($dataset))
		{
			foreach($dataset as $column_name => $column_data)
			{
				$table_headers[] = $column_name;
			}
		}
		return $table_headers;
	}
    
    /**
	 * Generate Edit Link in Table Data View
	 * @param string $tablename Table Name
	 * @param object $table_dataset Dataset to edit
	 * @return string url parameters for edit link
	 */
    private static function helper_get_row_edit_link($tablename, $table_dataset)
    {
        $pk_columns = TS_INF_COMFORT_DB_DATABASE::get_primary_key_columns($tablename);
        
        $where_clause_str = "";
        
        
        if(is_object($table_dataset))
        {
            $where_clause_array = array();
            
            foreach($table_dataset as $column_name => $column_data)
            {	
                if(in_array($column_name, $pk_columns))
                {
                    $where_clause_array[] = "`" . urlencode($tablename) . "`.`" . urlencode($column_name) . "`='" . urlencode($column_data) . "'";
                }
            }
            
            $where_clause_str = "&identifier=";
            $where_clause_str .= implode(" AND ", $where_clause_array);
            $where_clause_str = trim($where_clause_str);
            
        }
       
        
        return $where_clause_str;
    }
    
    /**
     * Generate Identifier Link for Field Search
     * @param string Table Name
     * @param  array $values Search-Values - Key: Column Name - Value: Search Term for the Column
     * @return string url parameters for search result page
     */
    private static function helper_get_search_identifier_parameter($tablename, $values)
    {
        $where_clause_str = "";
        
        if(is_array($values) && count($values) > 0)
        {
            $where_clause_array = array();
            
            foreach($values as $column_name => $column_data)
            {	
                if($column_data === "IS NULL")
                {
                    $where_clause_array[] = "`" . urlencode($tablename) . "`.`" . urlencode($column_name) . "` IS NULL";
                } else if(is_numeric($column_data))
            	{
                	$where_clause_array[] = "`" . urlencode($tablename) . "`.`" . urlencode($column_name) . "`=" . urlencode("'" . $column_data . "'");
            	} else {
            		$where_clause_array[] = "`" . urlencode($tablename) . "`.`" . urlencode($column_name) . "`" . urlencode(" LIKE '{percent}" . $column_data . "{percent}'");
            	}
            }
            
            $where_clause_str = "&identifier=";
            $where_clause_str .= implode(" AND ", $where_clause_array);
            $where_clause_str = trim($where_clause_str);
        }
        
        return $where_clause_str;
    }
    
    /**
	 * Generates row identifer in json format
	 * @param string $tablename Table Name
	 * @param object $table_dataset Dataset to edit
	 * @return string json string with row identifiers
	 */
    private static function helper_get_row_identifier($tablename, $table_dataset)
    {
        $pk_columns = TS_INF_COMFORT_DB_DATABASE::get_primary_key_columns($tablename);
        
        $result_array = array();
        
        
        if(is_object($table_dataset))
        {   
            foreach($table_dataset as $column_name => $column_data)
            {	
                if(in_array($column_name, $pk_columns))
                {
                    $result_array[$column_name] = $column_data;
                }
            }            
        }
       
        
        return json_encode($result_array);
    }
    
    /**
	 * Get and sanitize full text search search-parameter
	 */
    private static function helper_get_full_text_search_value()
    {
        $search_value = "";
        if(isset($_GET['tsinf_comfortdb_full_text_search']))
        {
            $search_value = htmlentities(strip_tags($_GET['tsinf_comfortdb_full_text_search']), ENT_QUOTES);
        }
        return $search_value;
    }
    
    /**
     * Hook to redirect from field search form to results
     */
    public static function helper_redirect_to_search_results_page()
    {
        if ( isset( $_GET['page'] ) && 
            isset( $_GET['table'] ) && 
            isset( $_GET['action'] ) && 
            $_GET['action'] === 'search' && 
            isset( $_POST['tsinf_comfortdb_plugin_edit_dataset'] ) && 
            isset( $_POST['tsinf_comfortdb_plugin_edit_dataset']['tsinf_comfortdb_plugin_edit_dataset_table'] )
           ) {
            $search_results = array();
            $post_array = $_POST['tsinf_comfortdb_plugin_edit_dataset'];
            if(check_admin_referer( 'action_tsinf_comfortdb_plugin_search_table_submit', '_wpnonce' ))
            {
                $values = array();
                
                if(is_array($post_array) && count($post_array) > 0)
                {
                    foreach($post_array as $key => $value)
                    {
                        if($key === 'tsinf_comfortdb_plugin_edit_dataset_table')
                        {
                            continue;
                        }
                        
                        $cur_key = sanitize_text_field($key);
                        $cur_val = sanitize_text_field($value);
                        
                        if($cur_val === TSINF_COMFORT_DB_SET_COL_VALUE_NULL)
                        {
                            $cur_val = "IS NULL";
                        }
                        
                        if(strlen($cur_key) > 0 && strlen($cur_val) > 0)
                        {
                            $values[$cur_key] = $cur_val;
                        }
                    }
                }
                                
                if(is_array($values) && count($values) > 0 && isset($post_array['tsinf_comfortdb_plugin_edit_dataset_table']) && strlen($post_array['tsinf_comfortdb_plugin_edit_dataset_table']) > 0)
                {
                    $tablename = sanitize_text_field($post_array['tsinf_comfortdb_plugin_edit_dataset_table']);
                    
                    if(strlen($tablename) > 0)
                    {
                        $search_identifiers = self::helper_get_search_identifier_parameter($tablename, $values);
                    }
                }
            }
            
            
            $redirect_url = admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s&action=search%s', $tablename, $search_identifiers));
            wp_redirect($redirect_url);   
            exit;
        }
    }
    
    /**
	 * Ajax Callback to get meta data of a table column
	 */
	public static function ajax_get_column_meta_data_callback()
	{
        if(
            isset($_POST['action']) && $_POST['action'] === 'get_column_meta_data' &&
            isset($_POST['table']) && 
            isset($_POST['column'])
        ) {
            $table = trim($_POST['table']);
            $column = trim($_POST['column']);
            
            check_ajax_referer('tsinf_comfortdb_plugin_get_meta_info_' . $column, 'security', true);
            
            if(strlen($table) > 0 && strlen($column) > 0)
           {
                $meta_infos = TS_INF_COMFORT_DB_DATABASE::get_column_metadata($table, $column);
                echo json_encode($meta_infos);
           }
                           
            
        }
        wp_die();
	}
	
	/**
	 * Ajax Callback to get data about the structure of a table
	 */
	public static function ajax_get_table_struct_meta_callback()
	{
	    if(
	        isset($_POST['action']) && $_POST['action'] === 'get_table_struct_meta' &&
	        isset($_POST['table'])
	        ) {
	            $table = trim(sanitize_text_field($_POST['table']));
	            
	            check_ajax_referer('tsinf_comfortdb_table_overview_row_' . $table, 'security', true);
	            
	            if(strlen($table) > 0)
	            {
	                $result_array = array();
	                $size = TS_INF_COMFORT_DB_DATABASE::get_used_space_table($table);
	                $engine = TS_INF_COMFORT_DB_DATABASE::get_table_engine($table);
	                if(is_numeric($size))
	                {
	                    $size = number_format($size, 2, ".", "");
	                    $size = sprintf("%s MB", $size);
	                }
	                
	                $result_array['size'] = $size;
	                $result_array['engine'] = $engine;
	                
	                wp_send_json($result_array);
	            }
	            
	            
	        }
	        wp_die();
	}
	
	/**
	 * Ajax Callback to export table to CSV
	 */
	public static function ajax_export_table_to_csv_callback()
	{
	    if(
	        isset($_POST['action']) && $_POST['action'] === 'export_table_to_csv' &&
	        isset($_POST['table'])
	        ) {
	            $table = trim(sanitize_text_field($_POST['table']));
	            
	            check_ajax_referer(TS_INF_TABLE_TO_CSV_NONCE, 'security', true);
	            
	            if(strlen($table) > 0)
	            {
	                $result = array();
	                
	                self::export_table_to_csv($table);
	                
	                wp_send_json($result);
	            }
	            
	            
	        }
	        wp_die();
	}
	
	/**
	 * Ajax Callback to export selected rows to CSV
	 */
	public static function ajax_export_rows_to_csv_callback()
	{
	    if(
	        isset($_POST['action']) && $_POST['action'] === 'export_rows_to_csv' &&
	        isset($_POST['rows']) && is_array($_POST['rows']) && count($_POST['rows']) > 0 &&
	        isset($_POST['table'])
	        )
	    {
	        global $wpdb;
	        
	        $rows = $_POST['rows'];
	        $tablename = htmlentities(strip_tags($_POST['table']), ENT_QUOTES);
	        
	        self::export_rows_to_csv($tablename, $rows);	        
	        
	    }
	}
    
    /**
	 * Ajax Callback to delete a row
	 */
    public static function ajax_tsinf_comfortdb_table_delete_rows()
    {   
        check_ajax_referer('tsinf_comfortdb_plugin_delete_table_dataset', 'security', true);
        
        define('ERROR_INVALID_POST_DATA', -1);
        define('ERROR_INVLAID_ROW_DATA', -2);
        define('ERROR_QUERY_ERROR', -3);
        
        $results = array();
        
        if(
            isset($_POST['action']) && $_POST['action'] === 'tsinf_comfortdb_table_delete_rows' && 
            isset($_POST['rows']) && is_array($_POST['rows']) && count($_POST['rows']) > 0 &&
            isset($_POST['table'])
        )
        {
            global $wpdb;
            
            $rows = $_POST['rows'];
            $tablename = htmlentities(strip_tags($_POST['table']), ENT_QUOTES);
            
            foreach($rows as $row)
            {
                $row_data_json_str = stripslashes(html_entity_decode($row, ENT_QUOTES));
                $row_data = (array) json_decode($row_data_json_str);
                
                
                if(is_array($row_data) && count($row_data) > 0)
                {
                    $where = '';
                    
                    $where_condition_counter = 0;
                    $row_data_length = count($row_data);
                    foreach($row_data as $field => $value)
                    {
                        $where_condition_counter++;
                        if($row_data_length === $where_condition_counter)
                        {
                            $where .= " " . $field . "=\"" . $value . "\"" . " ";
                        } else {
                            $where .= " " . $field . "=\"" . $value . "\"" .  " AND "; 
                        }
                    }
                        
                    try {
                        
                        $delete_result = $wpdb->query( "
                            DELETE
                            FROM " . $tablename . 
                            " WHERE " . $where . " 
                            LIMIT 1;" );

                        $results[] = array('rowdata' => $row_data, 'result' => $delete_result);
                        
                        
                    } catch(Exception $e)
                    {
                        // Error - Query Error
                        $results[] = array('rowdata' => $row_data, 'result' => ERROR_QUERY_ERROR);
                    }
                    
                } else {
                    // Error - Invalid Row Data sent
                    $results[] = array('rowdata' => $row_data, 'result' => ERROR_INVLAID_ROW_DATA);
                }
            }
            
            
            
        } else {
            // Error - Invalid Post Data
            $results[] = array('rowdata' => array(), 'result' => ERROR_INVLAID_ROW_DATA);
        }
        
        echo json_encode($results);
        
        wp_die();
    }
    
    /**
	 * Ajax Callback to confirm and hide risk message
	 */
	public static function ajax_risk_message_button_confirmed()
    {
        check_ajax_referer('tsinf_comfortdb_plugin_confirm_risk_message', 'security', true);
        
        update_option('tsinf_comfortdb_plugin_confirm_risk_message', 1);
        
        echo "1";
        
        wp_die();
    }
    
    /**
     * AJAX Callback to process action send via table cell context menu
     */
    public static function ajax_table_context_menu()
    {
        check_ajax_referer('tsinf_comfortdb_plugin_table_cell_menu', 'security', true);

        $result_content = '';
        
        if(
        isset($_POST['action']) && $_POST['action'] === 'table_context_menu' &&
        isset($_POST['type']) && isset($_POST['content'])
        ) {
            
            $type = trim(sanitize_text_field($_POST['type']));
            $content = $_POST['content'];
            
            
            switch($type)
            {
                case 'unserialize':
                    $content = stripslashes($content);
                    $result_content = maybe_unserialize($content);
                    $result_content = var_export($result_content, true);
                    
                    break;
                    
                case 'jsondecode':
                    $content = stripslashes($content);
                    $result_content = json_decode($content);
                    $result_content = var_export($result_content, true);
                    break;
                    
                case 'varexport':
                    $result_content = var_export($content, true);
                    break;
            }
        }
        
        echo $result_content;
        
        
        wp_die();
    }
    
    /**
     * Add table list to Adminbar
     */
    public static function add_tables_to_adminbar()
    {
        global $wp_admin_bar;

        if ( ! current_user_can( 'manage_options' ) || !is_admin_bar_showing()) {
            return;
        }
        
        $table_names = array();
        
        try {
            $table_names = TS_INF_COMFORT_DB_DATABASE::get_table_names();
        } catch(Exception $e)
        {
            $table_names = array();
        }
        
        if(is_array($table_names) && count($table_names) > 0)
        {
            $wp_admin_bar->add_menu( array(
                'id'    => 'tsinf_comfortdb_plugin_adminbar_menu',
                'parent' => null,
                'group'  => null,
                'title' => __('Database Tables', 'tsinf_comfortdb_plugin_textdomain'), //you can use img tag with image link. it will show the image icon Instead of the title.
                'href'  => admin_url('admin.php?page=tscomfortdb-mainpage'),
                'meta' => array (
                        'class' => 'tsinf_comfortdb_plugin_show_dbtables',
                        'title' => __('Open your database tables directly', 'tsinf_comfortdb_plugin_textdomain')
                )   
            ) );
            
            foreach($table_names as $tablename)
            {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'tsinf_comfortdb_plugin_adminbar_menu_table_' . $tablename,
                    'parent' => 'tsinf_comfortdb_plugin_adminbar_menu',
                    'group'  => null,
                    'title' => $tablename, //you can use img tag with image link. it will show the image icon Instead of the title.
                    'href'  => admin_url(sprintf('?page=tscomfortdb-mainpage&table=%s', $tablename)),
                    'meta' => array (
                        'class' => 'tsinf_comfortdb_plugin_adminbar_menu_table_' . $tablename,
                        'title' => sprintf(__('Table: %s', 'tsinf_comfortdb_plugin_textdomain'), $tablename)
                    )
                ) );
            }
        }
    }
    
    /**
     * Write table selected data to csv file
     * @param string $table tablename
     * @param array $rows rows to export
     */
    public static function export_rows_to_csv($tablename, $rows)
    {
        add_action('shutdown', function()
        {
            if($error = error_get_last())
            {
                // Catch Fatal Errors
                $state = json_encode(array('done' => 0, 'total' => 0, 'error' => $error['message']));
                set_transient(self::$progress_hash_rows_to_csv, $state, HOUR_IN_SECONDS);
            }
        });
        
        if(is_array($rows) && count($rows) > 0)
        {
            $where_condition_counter = 0;
            $row_data_length = count($rows);
            $where = '';
            
            foreach($rows as $row)
            {
                $where_condition_counter++;
                
                $row_data_json_str = stripslashes(html_entity_decode($row, ENT_QUOTES));
                $row_data = (array) json_decode($row_data_json_str);
                
                foreach($row_data as $field => $value)
                {
                    if($row_data_length === $where_condition_counter)
                    {
                        $where .= " `" . $field . "`=\"" . $value . "\"" . " ";
                    } else {
                        $where .= " `" . $field . "`=\"" . $value . "\"" .  " OR ";
                    }
                }
                
            }
        }
            
        global $wpdb;
        $table_data_sql = "SELECT *
                            FROM `" . $tablename . "`" .
                            " WHERE " . $where . "
                            LIMIT 100;";
                
        try {
            $filename = sprintf("%s-%s-SELECTION.csv", date("Y-m-d-H-i-s"), sanitize_title($tablename));
            
            $fp = fopen(TS_INF_COMFORT_DB_UPLOAD_DIR . $filename, 'w');
            
            $table_data = $wpdb->get_results($table_data_sql, ARRAY_A);
            
            if(is_array($table_data))
            {
                $dataset_total = count($table_data);
                
                if($dataset_total > 0)
                {
                    $table_entry_counter = 0;
                    foreach($table_data as $dataset)
                    {
                        $insert = array_values($dataset);
                        
                        $write_response = fputcsv($fp, $insert);
                                                
                        if($write_response === false)
                        {
                            // error and break?
                        }
                        
                        $table_entry_counter++;
                        
                        $state = json_encode(array('done' => $table_entry_counter, 'total' => $dataset_total, 'percent' => ($table_entry_counter / $dataset_total)));
                        set_transient(self::$progress_hash_rows_to_csv, $state, HOUR_IN_SECONDS);
                    }
                }
            }
            
            fclose($fp);
        } catch(Exception $exception)
        {
            $state = json_encode(array('done' => 0, 'total' => 0, 'error' => $exception->getMessage()));
            set_transient(self::$progress_hash_rows_to_csv, $state, HOUR_IN_SECONDS);
        }
            
            
            
            
        
    }
    
    /**
     * Write table data to csv file
     * @param string $table tablename
     */
    public static function export_table_to_csv($table)
    {
        if(file_exists(TS_INF_COMFORT_DB_UPLOAD_DIR) && is_writable(TS_INF_COMFORT_DB_UPLOAD_DIR)) {
            try {
                $filename = sprintf("%s-%s.csv", date("Y-m-d-H-i-s"), sanitize_title($table));
                $fp = fopen(TS_INF_COMFORT_DB_UPLOAD_DIR . $filename, 'w');
                
                global $wpdb;
                $table_data_sql = sprintf("SELECT * FROM `%s` LIMIT 5000;", $table);
                $table_data = $wpdb->get_results($table_data_sql, ARRAY_A);
                
                if(is_array($table_data))
                {
                    $dataset_total = count($table_data);
                    
                    if($dataset_total > 0)
                    {
                        $table_entry_counter = 0;
                        foreach($table_data as $dataset)
                        {
                            $insert = array_values($dataset);
                            
                            $write_response = fputcsv($fp, $insert);
                            
                            if($write_response === false)
                            {
                                // error and break?
                            }
                            
                            $table_entry_counter++;
                            
                            $state = json_encode(array('done' => $table_entry_counter, 'total' => $dataset_total, 'percent' => ($table_entry_counter / $dataset_total)));
                            set_transient(self::$progress_hash_table_to_csv[$table], $state, HOUR_IN_SECONDS);
                        }
                    }
                }
                
                fclose($fp);
            } catch(Exception $exception)
            {
                $state = json_encode(array('done' => 0, 'total' => 0, 'error' => $exception->getMessage()));
                set_transient(self::$progress_hash_table_to_csv[$table], $state, HOUR_IN_SECONDS);
            }
            
        } else {
            $state = json_encode(array('done' => 0, 'total' => 0, 'error' => __('Upload Directory not exists', 'tsinf_comfortdb_plugin_textdomain')));
            set_transient(self::$progress_hash_table_to_csv[$table], $state, HOUR_IN_SECONDS);
        }
    }
}
?>