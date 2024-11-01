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

if(!class_exists('TSINF_FILEMANAGER'))
{
    class TSINF_FILEMANAGER
    {
        private $directory;
        private $allowed_admin_pages;
        
        public function __construct($directory, $allowed_admin_pages = array())
        {
            $this->directory = rtrim($directory, '/');
            $this->allowed_admin_pages = $allowed_admin_pages;
            
            $this->establish_connections();
            
            $this->provide_download();
        }
        
        public function establish_connections()
        {
            add_action('admin_enqueue_scripts', array($this, 'load_backend_scripts'));
            add_action('wp_ajax_tsinf-filemanager-delete-files', array($this, 'ajax_delete_files'));
            add_action('wp_ajax_tsinf-filemanager-zipdl-files', array($this, 'ajax_zipdl_files'));
        }
        
        public static function load_backend_scripts()
        {
            wp_enqueue_style('ts-db-export-filemanager-css', plugins_url("/ts-comfort-database/css/filemanager.css"));
            wp_enqueue_script('ts-db-export-filemanager-js', plugins_url("/ts-comfort-database/js/filemanager.js"), array('jquery'));
        }
        
        /**
         * Provide Download
         */
        public function provide_download()
        {
            if(isset($_GET['page']))
            {
                $page = htmlentities(strip_tags($_GET['page']), ENT_QUOTES);
                if($this->is_allowed($page))
                {
                    if(isset($_GET['download']))
                    {
                        $filename = sanitize_file_name($_GET['download']);
                        if(file_exists($this->directory . "/" . $filename))
                        {
                            $filepath = $this->directory . "/" . $filename;
                            
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename="' . $filename . '"');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate');
                            header('Pragma: public');
                            header('Content-Length: ' . filesize($filepath));
                            
                            readfile($filepath);
                            exit;
                        }
                    }
                }
            }
        }
        
        /**
         * Check if you are on an allowed admin page
         * @param string $page Page-Slug
         * @return boolean true if you are on an allowed admin page, otherwise false
         */
        public function is_allowed($page)
        {
            $allowed = (is_admin() && is_array($this->allowed_admin_pages) && in_array($page, $this->allowed_admin_pages));
            return $allowed;
        }
        
        public function ajax_delete_files()
        {            
            check_ajax_referer('tsinf-filemanager-delete-files', 'security', true);
            
            $deleted_files = array();
            if(is_string($this->directory) && strlen($this->directory) > 0 && file_exists($this->directory))
            {
                $filenames = isset($_POST['filenames']) ? $_POST['filenames'] : array();
                if(is_array($filenames) && count($filenames))
                {
                    WP_Filesystem();
                    global $wp_filesystem;
                    
                    foreach($filenames as $filename)
                    {
                        $filename_sanitized = sanitize_file_name($filename);
                        
                        $filepath = sprintf('%s/%s', $this->directory, $filename_sanitized);
                        
                        if(file_exists($filepath))
                        {
                            $file_result = $wp_filesystem->delete($filepath);
                            if($file_result === true)
                            {
                                $deleted_files[] = md5($filename_sanitized);
                            }
                        }
                    }
                }
            
            }
            
            wp_send_json($deleted_files);
            
            wp_die();
        }
        
        /**
         * AJAX Callback to zip files
         */
        public function ajax_zipdl_files()
        {            
            check_ajax_referer('tsinf-filemanager-zip-files', 'security', true);
            
            $result = -1;
            
            if(is_string($this->directory) && strlen($this->directory) > 0 && file_exists($this->directory))
            {
                $zip_archive = new ZipArchive();
                $zip_name = sprintf('%s/%s.zip', $this->directory, date('Y-m-d-G-i-s'));
             
                if ($zip_archive->open($zip_name, ZipArchive::CREATE) === TRUE) {
                    $filenames = $_POST['filenames'];
                    if(is_array($filenames) && count($filenames))
                    {
                        foreach($filenames as $filename)
                        {
                            $filename_sanitized = sanitize_file_name($filename);
                            
                            $filepath = sprintf('%s/%s', $this->directory, $filename_sanitized);
                            
                            if(file_exists($filepath))
                            {
                                $zip_archive->addFile($filepath, $filename_sanitized);
                            }
                        }
                    }
                    
                    $zip_archive->close();
                    
                    $result = $zip_name;
                }
            }
            
            echo $result;
            
            wp_die();
        }
        
        /**
         * Helper function to get raw download link
         * @return string
         */
        public function get_current_url_without_dl_param()
        {
            $url_without_dl_parameter = get_admin_url(null, 'admin.php?page=tscomfortdb-filemanager');
            // $parsed_url = parse_url($current_url);
            // $url_query = $parsed_url['query'];
            // $url_parts = array();
            // parse_str($url_query, $url_parts);
            // if(isset($url_parts['download']))
            // {
            //     unset($url_parts['download']);
            // }
            
            // $base_url = sprintf("%s%s", rtrim(get_site_url(), "/"), $_SERVER['REDIRECT_SCRIPT_URL'],  http_build_query($url_parts));
            
            // $url_without_dl_parameter = $base_url . "?" . http_build_query($url_parts);
            
            return $url_without_dl_parameter;
        }
        
        /**
         * Render File Manager Admin Page
         */
        public function render_file_manager()
        {            
            WP_Filesystem();
            global $wp_filesystem;
            $filelist = array();
            if(file_exists($this->directory))
            {
                $filelist = $wp_filesystem->dirlist($this->directory);
            }
            ?>
            
			<div id="tssesl_gps_filter_box_files" class="tssesl_gps_filter_box sticky_header">
				<p class="filter_box_headline tsinf_search_slug_line blue">
					<span class="tssesl_gps_filter_box_col tsinf_file_check"><input type="checkbox" class="tsinf_check_all" value="1" /></span>
					<span class="tssesl_gps_filter_box_col tsinf_file_name">Name</span>
					<span class="tssesl_gps_filter_box_col tsinf_file_size">Size</span>
					<span class="tssesl_gps_filter_box_col tsinf_file_date">Date</span>
					<span class="tssesl_gps_filter_box_col tsinf_file_utils" colspan="2"></span>
				</p>
				<div class="files_toolbar">
					<span class="tsinf_fman_batch_action toolbar_element_wrap">
						<span class="toolbar_label"><?php _e('Selected Elements:', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
						<select class="tsinf_fman_batch_action_select toolbar_element">
							<option value="-1"><?php _e('Select Action', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="zipdl" data-auth="<?php echo wp_create_nonce('tsinf-filemanager-zip-files'); ?>"><?php _e('ZIP selected files', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
							<option value="delete" data-auth="<?php echo wp_create_nonce('tsinf-filemanager-delete-files'); ?>"><?php _e('Delete selected files', 'tsinf_comfortdb_plugin_textdomain'); ?></option>
						</select>
						<span class="tsinf_symbol_button tsinf_fman_batch_action_submit"><?php _e('Go', 'tsinf_comfortdb_plugin_textdomain'); ?></span>
						
					</span>
				</div>
    			<?php 
    			if(is_array($filelist) && count($filelist) > 0)
    			{
    			    // Newest Files on top
    			    $filelist = array_reverse($filelist);
    			    
    			    $current_url_without_dl_parameter = $this->get_current_url_without_dl_param();
    			    
    			    $file_row_counter = 0;
    			    foreach($filelist as $file)
    			    {
    			        $file_row_counter++;
    			        
    			        $class_even_odd = "odd";
    			        if($file_row_counter % 2 === 0)
    			        {
    			            $class_even_odd = "even";
    			        }
    			        
    			        $size_mb = $file['size']  / 1024 / 1024;
    			        $size_mb = number_format($size_mb, 4);
    			        ?>
    			        <span class="tsinf_search_slug_line <?php echo $class_even_odd; ?> <?php echo md5($file['name']); ?>">
    			        	<span class="tssesl_gps_filter_box_col tsinf_file_check" data-filename="<?php echo $file['name']; ?>"><input type="checkbox" value="<?php echo $file['name']; ?>" /></span>
    			        	<span class="tssesl_gps_filter_box_col tsinf_file_name"><?php echo $file['name']; ?></span>
    			        	<span class="tssesl_gps_filter_box_col tsinf_file_size"><?php echo $size_mb; ?> MB</span>
    			        	<span class="tssesl_gps_filter_box_col tsinf_file_date"><?php echo date("d.m.Y g:H", strtotime(sprintf("%s %s", $file["lastmod"], $file["time"]))); ?></span>
    			        	<span class="tssesl_gps_filter_box_col tsinf_file_utils"><a class="tsinf_fm_util_lnk download_file" href="<?php echo sprintf('%s&download=%s', $current_url_without_dl_parameter, $file['name']); ?>"><?php _e('Download', 'tsinf_comfortdb_plugin_textdomain'); ?></a></span>
    			        </span>
    			        <?php 
    			    }
    			}
    			?>
    			
    			</div>
    		</div>
    		<?php
        }
    }
}

