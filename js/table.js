var tsinf_rows_to_csv_refresh_progress_timer;
function tsinf_rows_to_csv_refresh_progress(target_progress)
{
	jQuery.ajax({
		url: TSINF_COMFORT_DB_TABLE_JS.checkerurl + "?hash=" + TSINF_COMFORT_DB_TABLE_JS.progress_hash_rows_to_csv,
		success:function(data){
			try {
				var percent_total_val = (data.percent * 100);
				var percent_total = percent_total_val.toFixed(2) + "%";
				target_progress.prop("value", data.percent);
				target_progress.text(percent_total);
				
				if (data.percent >= 1 || data.hasOwnProperty('error')) {
					window.clearInterval(tsinf_rows_to_csv_refresh_progress_timer);
					
					var message_target = target_progress.closest(".tsinf_cdb_progress_row").find("th");
					if(data.hasOwnProperty('error'))
					{
						message_target.html('<div class="tsinf_comfort_database_progress_error" style="text-align: left;">' + data.error + '</div>');
					} else {
						message_target.html('<div class="tsinf_comfort_database_progress_success" style="text-align: left;">' + TSINF_COMFORT_DB_TABLE_JS.export_finished + '<a href="' + TSINF_COMFORT_DB_TABLE_JS.filemanager_url + '">' + TSINF_COMFORT_DB_TABLE_JS.filemanager_name + '</a></div>');
					}
					

				}
			
			} catch(e)
			{
				window.clearInterval(tsinf_rows_to_csv_refresh_progress_timer);
				console.log(e);
			}
		}
	});
}

jQuery(document).ready(function(){
    jQuery(".row_select_all").click(function(){
        var rows = jQuery(".row_select");
        if(rows.length > 0)
        {
		    if(jQuery(this).is(":checked"))
            {
                rows.prop("checked", true);
            } else {
                rows.prop("checked", false);
            }
        }
    });
    
    jQuery(".tsinf_symbol_button.table_new_dataset, .tsinf_comfortdb_row_edit").click(function(){
        var data;
        
        var tablename = jQuery(this).closest(".tsinf_comfortdb_table").data("tablename");
        
        // get_rendered_input_form
        if(jQuery(this).hasClass("tsinf_comfortdb_row_edit"))
        {
            var identifier_cells = jQuery(this).closest("tr").find("td[data-pk]");
            var identifiers = [];
            
            identifier_cells.each(function(){
                var current_pk_string_json_obj = jQuery(this).data("pk");
                identifiers.push(current_pk_string_json_obj);
            });
            
            
            // Ajax Call to get rendered form with data
            data = {
				'action': 'get_rendered_input_form',
                'table': tablename,
				'identifiers': identifiers
			};
        } else if(jQuery(this).hasClass("table_new_dataset"))
        {
            // Ajax Call to get empty rendered form
            data = {
				'action': 'get_rendered_input_form',
                'table': tablename
			};
        }
        
        jQuery.post(ajaxurl, data, function(response) {
            console.log(response);
            var dialog = tsinf_comfort_db_create_dialog("Bearbeiten", response);
            jQuery("#wpcontent").append(dialog);
        });
    });
    
    function tsinf_db_comfort_toggle_table_columns()
    {
        var unchecked_columns = jQuery("#select_columns input:checkbox:not(:checked)");
        
        // Show all
        jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table th, .tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table td").show();
        
        // Hide unchecked columns
        unchecked_columns.each(function(){
            var columnname = jQuery(this).closest(".tsinf_select_menu_option").data("colname");
            jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table").find("[data-column-name='" + columnname + "']").hide();
        });
    }
    
    jQuery("#select_columns .tsinf_select_menu_option").click(function() {
        var checkbox = jQuery(this).find("[type='checkbox']");
        var state = checkbox.prop("checked");
        checkbox.prop("checked", !state);
        
        tsinf_db_comfort_toggle_table_columns();
    });
    
    jQuery("#select_columns .tsinf_select_menu_option [type='checkbox']").click(function(e) {
        tsinf_db_comfort_toggle_table_columns();
        
        e.stopPropagation();
    });
    
    jQuery("#tsinf_comfortdb_table_filter").keyup(function(){
        var input = jQuery(this);
        var filter = jQuery.trim(input.val().toUpperCase());
        var table_body = jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table tbody");
        var tr = table_body.find("tr:not(.tsinf_comfortdb_column_headline)");
        var i = 0;
        var td;
        
        if(filter.length < 1)
        {
            tr.show();
            return;
        }
        
        tr.each(function(tr_key,tr_val){
            td = jQuery(this).find("td");
            var found = false;
            td.each(function(td_key,td_val){
                if(jQuery(this).text().toUpperCase().indexOf(filter) > -1)
                {
                    found = true;
                    return false;
                }
            });
            
            if(found === true)
            {
                jQuery(this).show();
            } else {
                jQuery(this).hide();
            }
        });  
    });
    
    jQuery(".tsinf_comfortdb_toolbar.tabledata .tsinf_select_menu#select_action .tsinf_select_menu_option.delete").click(function() {
        var result = confirm(TSINF_COMFORT_DB_TABLE_JS.really_delete);
        if(result === true)
        {
            var selected_rows = jQuery(".row_select:checked");
            var selected_tr = selected_rows.closest("tr");
            var selected_row_ids = [];

            selected_rows.each(function(){
                selected_row_ids.push(jQuery(this).val());
            });

            var tablename = jQuery(".tsinf_comfortdb_table").data("tablename");
            var auth = jQuery(this).data("auth");

            var data = {
                'action': 'tsinf_comfortdb_table_delete_rows',
                'rows': selected_row_ids,
                'table': tablename,
                'security': auth
            };

            selected_tr.addClass("prepared_to_delete");

            jQuery.post(ajaxurl, data, function(response) {
                var response_not_readable = false;
                var error_occured = false;

                try {
                    var response_json = jQuery.parseJSON(response);
                    var result = parseInt(response_json[0].result);
                    var error_message = '';
                    if(result < 0)
                    {
                        error_occured = true;
                        error_message = TSINF_COMFORT_DB_TABLE_JS.error_occured + ': ';
                            
                        switch(result)
                        {
                            case -1:
                                error_message += TSINF_COMFORT_DB_TABLE_JS.error_post_data;
                                break;
                            case -2:
                                error_message += TSINF_COMFORT_DB_TABLE_JS.error_row_data;
                                break;
                            case -3:
                                error_message += TSINF_COMFORT_DB_TABLE_JS.error_query;
                                break;
                        }
                    }
                    
                } catch(error)
                {
                    response_not_readable = true;
                    error_occured = true;
                    error_message = TSINF_COMFORT_DB_TABLE_JS.error_occured + ': ';
                    error_message += TSINF_COMFORT_DB_TABLE_JS.error_server_response;
                }
                
                if(error_occured === true)
                {
                    alert(error_message);
                } else {
                    selected_tr.fadeOut( "slow", function() {
                        selected_tr.remove();
                    });
                }
            });
        }
    });

	jQuery(".tsinf_comfortdb_toolbar.tabledata .tsinf_select_menu#select_action .tsinf_select_menu_option.export_csv").click(function() {
		var current_option = jQuery(this);
		
		var dropdown = current_option.closest(".tsinf_select_menu_options_wrap");
		dropdown.hide();
		
		var selected_rows = jQuery(".row_select:checked");
		
		if(selected_rows !== undefined && selected_rows.length > 0)
		{
	        var selected_row_ids = [];
	
	        selected_rows.each(function(){
	            selected_row_ids.push(jQuery(this).val());
	        });
	
			var table_ref = jQuery(".tsinf_comfortdb_table");
			var table_head_ref = table_ref.find("thead");
			
			var tablename = table_ref.data("tablename");
			var colcount = table_ref.data("colcount");
			// include checkbox and edit link columns
			var colcount_total = colcount + 2;
	        var auth = current_option.data("auth");
	
			var existing_progress_row = table_head_ref.find(".tsinf_cdb_progress_row");
			if(existing_progress_row !== undefined && existing_progress_row.length > 0)
			{
				existing_progress_row.remove();
			}
			
			table_head_ref.append('<tr class="tsinf_cdb_progress_row"><th colspan="' + colcount_total + '"><progress val="0.0" max="1" class="tsinf-progress-bar tsinf-export-to-csv-progress-bar"></progress></th></tr>');
	
			var progressbar = table_head_ref.find(".tsinf_cdb_progress_row .tsinf-export-to-csv-progress-bar");
	
	        var data = {
	            'action': 'export_rows_to_csv',
	            'rows': selected_row_ids,
	            'table': tablename,
	            'security': auth
	        };
					
			jQuery.post(ajaxurl, data, function(response) {
			});
			
			tsinf_rows_to_csv_refresh_progress_timer = window.setInterval(tsinf_rows_to_csv_refresh_progress, 500, progressbar); 
		}
		
	});
	
    
    jQuery("#tsinf_comfortdb_table_header_elements .tsinf_comfort_db_header_mobile_switch .arrow-up").click(function() {
        jQuery('html,body').stop(true,false).animate({ scrollTop: 0 }, 'slow');
    });

	jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table tr td .tcellmenubutton").click(function() {
		var all_menus = jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table tr td .tcellmenu_container");
		var parent_cell = jQuery(this).closest("td");
		var related_menu = parent_cell.find(".tcellmenu_container");
		all_menus.hide();
		related_menu.show();
	});
	
	jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table tr td .tcellmenu_container .menu_close").click(function() {
		var current_menu = jQuery(this).closest(".tcellmenu_container");
		current_menu.hide();
	});
	
	jQuery(".tsinf_comfortdb_table_data_wrapper .tsinf_comfortdb_table tr td .tcellmenu_container .tcellmenu_option").click(function() {
		var option = jQuery(this);
		var menu = option.closest(".tcellmenu_container");
		var type = option.data("val");
		var textfield = option.closest("td").find(".dbcontentcodewrap");
		
		menu.hide();
		
		if(type === 'copy')
		{
			textfield.select();
			document.execCommand("copy");
		} else {
		
			var data = {
				action: 'table_context_menu',
				type: type,
				content: textfield.val(),
				security: TSINF_COMFORT_DB_TABLE_JS.nonce_cell_menu
			}
			
			jQuery.post(ajaxurl, data, function(response) {
				var table_data_wrapper = jQuery("#wpbody #wpbody-content .tsinf_comfortdb_table_data_wrapper");
				var existing_overlay = table_data_wrapper.find("#tsinf_comfortdb_overlay");
				if(existing_overlay !== undefined && existing_overlay.length > 0)
				{
					existing_overlay.remove();
				}
				
				table_data_wrapper.append('<div id="tsinf_comfortdb_overlay"><div id="tsinf_comfortdb_overlay_inner"><div class="tsinf_ovl_toolbar"><span class="tsinf_ovl_toolbar_item copy_to_clipboard">' + TSINF_COMFORT_DB_TABLE_JS.copy_to_clipboard + '</span><span class="tsinf_close_ovl"><svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></span></div><textarea readonly="readonly">' + response + '</textarea></div></div>');
				
				var close = jQuery("#wpbody #wpbody-content .tsinf_comfortdb_table_data_wrapper #tsinf_comfortdb_overlay #tsinf_comfortdb_overlay_inner .tsinf_ovl_toolbar .tsinf_close_ovl");
				close.click(function() {
					var current_overlay = jQuery(this).closest("#tsinf_comfortdb_overlay").remove();
					current_overlay.remove();
				});
				
				var copy_to_clipboard = jQuery("#wpbody #wpbody-content .tsinf_comfortdb_table_data_wrapper #tsinf_comfortdb_overlay #tsinf_comfortdb_overlay_inner .tsinf_ovl_toolbar .tsinf_ovl_toolbar_item.copy_to_clipboard");
				copy_to_clipboard.click(function() {
					var textfield = jQuery(this).closest("#tsinf_comfortdb_overlay").find("textarea");
					textfield.select();
					document.execCommand("copy");
				});
				
			});
			
		}
		
		
		
	});
    
    jQuery(window).scroll(function() {
        var header = jQuery("#tsinf_comfortdb_table_header_elements");
        var current = jQuery(this).scrollTop();
        if(current > 300)
        {
            header.addClass("scrolldown");
        } else {
            header.removeClass("scrolldown");
        }
    });
});

