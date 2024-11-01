var tsinf_db_exp_refresh_progress_timer;

function tsinf_db_exp_refresh_progress()
{
	jQuery.ajax({
		url: TS_EXPDB_JS_CLASS.checkerurl + "?hash=" + TS_EXPDB_JS_CLASS.progress_hash,
		success:function(data){
			try {
				var progress_table_number = jQuery("#tsinf-db-export-info-area .progress-table-number");
				var progress_total_number = jQuery("#tsinf-db-export-info-area .progress-total-number");
				
				var progress_table = jQuery("#tsinf-db-export-info-area .progress-table");
				var progress_total = jQuery("#tsinf-db-export-info-area .progress-total");
				
				var progress_success = jQuery("#tsinf-db-export-info-area .progress-success");
				var progress_error = jQuery("#tsinf-db-export-info-area .progress-error");
				
				var progress_current_entry = jQuery("#tsinf-db-export-info-area .progress-current-entry");
				var progress_current_table = jQuery("#tsinf-db-export-info-area .progress-current-table");
				
				var percent_table_val = (data.percent_table * 100);
				var percent_table = percent_table_val.toFixed(2) + "%";
				progress_table.prop("value", data.percent_table);
				progress_table.text(percent_table);
				progress_table_number.text(percent_table);
				
				var percent_total_val = (data.percent_total * 100);
				var percent_total = percent_total_val.toFixed(2) + "%";
				progress_total.prop("value", data.percent_total);
				progress_total.text(percent_total);
				progress_total_number.text(percent_total);
				
				progress_current_entry.text(data.current_table + " (" + data.current_table_entries_done + "/" + data.current_table_entries_total + ")");
				progress_current_table.text(data.current_table);
				
				if (data.percent_total >= 1 || data.hasOwnProperty('error')) {
					window.clearInterval(tsinf_db_exp_refresh_progress_timer);
					jQuery("#ts-db-export-start-export").removeAttr("disabled");
					
					progress_current_entry.text("");
					progress_current_table.text("");
					
					progress_success.html(TS_EXPDB_JS_CLASS.export_finished + "<a href='" + TS_EXPDB_JS_CLASS.filemanager_url + "'>" + TS_EXPDB_JS_CLASS.filemanager_name + "</a>")
					
					progress_error.text(data.error);
				}
			
			} catch(e)
			{
				window.clearInterval(tsinf_db_exp_refresh_progress_timer);
				console.log(e);
			}
		}
	});
}

function tsinf_db_exp_enable_disable_create_db(cb)
{
	var cd_create_db = jQuery("#tssesl_gps_filter_box_options .option_create_db");
	
	if(cb.is(":checked"))
	{
		cd_create_db.removeAttr("checked");
		cd_create_db.prop("disabled", "disabled");
	} else {
		cd_create_db.removeAttr("disabled");
	}
}

jQuery(document).ready(function() {
	jQuery("#ts-db-export-start-export").click(function(e) {
		e.preventDefault();
		e.stopPropagation();		
		
		var button = jQuery(this);
		var form = button.closest("#ts-db-export-start-export-settings-form");
		var save_and_download = form.find("#export_target_save_and_download");
		var auth = form.data("auth");
		
		button.prop("disabled", "disabled");
		
		var form_data = form.serialize();
			
		var data = {
			'action': 'tsinf-db-export-start',
			'form_data': form_data,
            'security': auth
		};
				
		jQuery.post(ajaxurl, data, function(response) {
			 console.log(response);
			 
			 if(save_and_download.is(":checked"))
			 {
				 window.location.href = window.location.href + "&download=" + response;
			 }
			 
		 });
		 
		 tsinf_db_exp_refresh_progress_timer = window.setInterval(tsinf_db_exp_refresh_progress, 500); 
		 
		
		return false;
		
	});
	
	jQuery("#tssesl_gps_filter_box_options .option_split_by_table").click(function() {
		var cb = jQuery(this);
		
		tsinf_db_exp_enable_disable_create_db(cb);
	});
	
	jQuery("#tssesl_gps_filter_box_options .tsinf_search_slug_line").not(".filter_box_headline").click(function() {
		var cb = jQuery(this).find(".tssesl_gps_filter_box_col input[type='checkbox']");
		
		if(cb.is(":checked"))
		{
			cb.removeAttr("checked");
		} else {
			cb.prop("checked", "checked");
		}
		
		if(cb.hasClass("option_split_by_table"))
		{
			tsinf_db_exp_enable_disable_create_db(cb);
		}
	});
	
	jQuery("#tssesl_gps_filter_box_options .tsinf_search_slug_line").not(".filter_box_headline").mouseover(function() {
		jQuery(this).addClass("hover");
	});
	
	jQuery("#tssesl_gps_filter_box_options .tsinf_search_slug_line").not(".filter_box_headline").mouseout(function() {
		jQuery(this).removeClass("hover");
	});
	
	var table = jQuery("#ts-db-export-start-export-settings-form #tssesl_gps_filter_box_tables");
	var rows = table.find(".tsinf_search_slug_line").not(".filter_box_headline");
	
	if(rows.length > 0)
	{
		rows.each(function() {
			var row = jQuery(this);
			var target_size = row.find(".tsinf_comfortdb_used_space");
			var table = row.data("table-name");
			var auth = row.data("auth");
			
			var data = {
				'action': 'get_table_struct_meta',
				'table': table,
				'security': auth
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				try {
					target_size.html(response.size);
				} catch(e)
				{
				}
			});
		});
	}
});