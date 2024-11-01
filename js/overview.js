var tsinf_table_to_csv_refresh_progress_timer;
var tsinf_get_row_metadata_timer;
var tsinf_get_row_metadata = true;
var current_row_meta_data = 0;
var tsinf_table_meta_cache = new Array();

function tsinf_table_to_csv_refresh_progress(target_progress)
{
	var tablename = target_progress.closest(".tsinf_comfortdb_table_overview_row").data("table-name");
	table_to_csv_hash = TSINF_COMFORT_OVERVIEW_JS.progress_hash_table_to_csv[tablename];
	
	jQuery.ajax({
		url: TSINF_COMFORT_OVERVIEW_JS.checkerurl + "?hash=" + table_to_csv_hash,
		success:function(data){
			try {
				var percent_total_val = (data.percent * 100);
				var percent_total = percent_total_val.toFixed(2) + "%";
				target_progress.prop("value", data.percent);
				target_progress.text(percent_total);
				
				if (data.percent >= 1 || data.hasOwnProperty('error')) {
					window.clearInterval(tsinf_table_to_csv_refresh_progress_timer);
					
					var message_target = target_progress.closest("tr").find(".export_to_csv_wrap");
					if(data.hasOwnProperty('error'))
					{
						message_target.html('<div class="tsinf_comfort_database_progress_error"><small>' + data.error + '</small></div>');
					} else {
						message_target.html('<div class="tsinf_comfort_database_progress_success"><small>' + TSINF_COMFORT_OVERVIEW_JS.export_finished + '<a href="' + TSINF_COMFORT_OVERVIEW_JS.filemanager_url + '">' + TSINF_COMFORT_OVERVIEW_JS.filemanager_name + '</small></a></div>');
					}
					

				}
			
			} catch(e)
			{
				window.clearInterval(tsinf_table_to_csv_refresh_progress_timer);
				console.log(e);
			}
		}
	});
}


function tsinf_get_table_item_metainfo(table, info)
{
	try {
		var row = jQuery(".tsinf_comfortdb_table_overview_row[data-table-name='" + table + "']");
		
		if(row === undefined || row.length < 1)
		{
			return false;
		}
		
		var target = null;
		switch(info)
		{
			case "size":
				target = row.find(".tsinf_comfortdb_used_space");
				break;
			
			case "engine":
				target = row.find(".tsinf_comfortdb_engine");
				break;
		} 
		
		if(target === null || target.length < 1)
		{
			return false;
		}
		
		var auth = row.data("auth");
						
		var stored_cache = JSON.parse(sessionStorage.getItem("tsinf_table_meta_cache"));
		
		if(stored_cache !== null && stored_cache.hasOwnProperty(table))
		{
			target.html(stored_cache[table][info].toString());
		} else {
			var data = {
				'action': 'get_table_struct_meta',
				'table': table,
				'security': auth
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				try {
					target.html(response[info]);
										
					tsinf_table_meta_cache[table] = {"size": response.size.toString(), "engine": response.engine.toString()};
					window.sessionStorage.setItem("tsinf_table_meta_cache", JSON.stringify(Object.assign({}, tsinf_table_meta_cache)));
				} catch(e)
				{
				}
				
			});
		}

	} catch(e)
	{
		console.log(e);
		window.clearInterval(tsinf_get_row_metadata_timer);
	}
}

function tsinf_get_table_metainfo()
{
	try {
		var table = jQuery("#tsinf_comfortdb_table_overview");
		var rows = table.find(".tsinf_comfortdb_table_overview_row");
		
				
		if(tsinf_get_row_metadata === true && rows !== undefined)
		{
			var table_count = rows.length;
			if(table_count > 0 && current_row_meta_data < table_count)
			{
				tsinf_get_row_metadata = false;
				var row = rows.eq(current_row_meta_data);
				var target_size = row.find(".tsinf_comfortdb_used_space");
				var target_engine = row.find(".tsinf_comfortdb_engine");
				var table = row.data("table-name");
				var auth = row.data("auth");
								
				var stored_cache = JSON.parse(sessionStorage.getItem("tsinf_table_meta_cache"));
				
				if(stored_cache !== null && stored_cache.hasOwnProperty(table))
				{
					target_size.html(stored_cache[table].size.toString());
					target_engine.html(stored_cache[table].engine.toString());
					
					current_row_meta_data++;
					tsinf_get_row_metadata = true;
				} else {
					var data = {
						'action': 'get_table_struct_meta',
						'table': table,
						'security': auth
					};
					
					jQuery.post(ajaxurl, data, function(response) {
						try {
							target_size.html(response.size);
							target_engine.html(response.engine);
							current_row_meta_data++;
							tsinf_get_row_metadata = true;
							
							tsinf_table_meta_cache[table] = {"size": response.size.toString(), "engine": response.engine.toString()};
							window.sessionStorage.setItem("tsinf_table_meta_cache", JSON.stringify(Object.assign({}, tsinf_table_meta_cache)));
						} catch(e)
						{
						}
						
					});
				}
				
			} else {
				window.clearInterval(tsinf_get_row_metadata_timer);
			}
		}
	} catch(e)
	{
		console.log(e);
		window.clearInterval(tsinf_get_row_metadata_timer);
	}
}

jQuery(document).ready(function() {
	if(jQuery("#tsinf_comfortdb_table_overview").hasClass("disable_loading_meta") === false)
	{
		tsinf_get_row_metadata_timer = window.setInterval(tsinf_get_table_metainfo, 1000);
	}
	
	jQuery("#tsinf_comfortdb_table_overview .tsinf_comfortdb_table_overview_row .tsinf_comfortdb_options .export_to_csv").click(function() {
			var current_button = jQuery(this);
			var current_row = current_button.closest("tr");
			var table = current_row.data("table-name");
			var progressbar = current_row.find(".tsinf_comfortdb_options .export-complete-table-to-csv");
			
			current_button.fadeOut();
			progressbar.fadeIn();
			
			var data = {
				'action': 'export_table_to_csv',
				'table': table,
				'security': TSINF_COMFORT_OVERVIEW_JS.nonce
			};
			
			jQuery.post(ajaxurl, data, function(response) {
			});
			
			tsinf_table_to_csv_refresh_progress_timer = window.setInterval(tsinf_table_to_csv_refresh_progress, 500, progressbar); 
	});
	
	jQuery("#tsinf_comfortdb_table_overview .tsinf_comfortdb_table_overview_row .tsinf_loadmeta_link").click(function () {
		var link = jQuery(this);
		var col = link.closest("td");
		var table = col.closest(".tsinf_comfortdb_table_overview_row").data("table-name");
		var info = col.hasClass("tsinf_comfortdb_engine") ? "engine" : "size";
		
		tsinf_get_table_item_metainfo(table, info);
	});
	
});


