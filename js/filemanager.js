jQuery(document).ready(function() {
	jQuery("#tssesl_gps_filter_box_files .files_toolbar .tsinf_fman_batch_action .tsinf_fman_batch_action_submit").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var button = jQuery(this);
		var wrap = button.closest(".tsinf_fman_batch_action");
		var file_manager_wrap = button.closest("#tssesl_gps_filter_box_files");
		var select = wrap.find(".tsinf_fman_batch_action_select");
		var selected_option = select.find("option:selected");
		
		var selected_action = select.val();
		
		var selected_checkboxes = file_manager_wrap.find(".tsinf_file_check input:checked").not(".tsinf_check_all");
		
		var selected_files = [];
		
		selected_checkboxes.each(function() {
			var cbwrap = jQuery(this).closest(".tsinf_file_check");
			var filename = cbwrap.data("filename");
			selected_files.push(filename);
		});
				
		if(selected_action !== '-1')
		{
			var data = [];
			var auth = selected_option.data("auth");
			
			if(selected_action === 'zipdl')
			{
				data = {
						'action': 'tsinf-filemanager-zipdl-files',
						'filenames': selected_files,
			            'security': auth
					};
			} else if(selected_action === 'delete')
			{
				data = {
						'action': 'tsinf-filemanager-delete-files',
						'filenames': selected_files,
			            'security': auth
					};
			}
						
			jQuery.post(ajaxurl, data, function(response) {
				 try {
					 if(response.length > 0)
					 {
						 if(selected_action === 'delete')
						 {
							 var selector = "";
							 jQuery.each(response, function(k,v) {
								 selector += ".tsinf_search_slug_line." + v + ",";
							 });
							 
							 if(selector.length > 0)
							 {
								 selector = jQuery.trim(selector);
								 selector = selector.substring(0, selector.length - 1);
								 
								 var selected_rows = jQuery(selector);
								 
								 selected_rows.fadeOut( "slow", function() {
									 selected_rows.remove();
			                    });
							 }
						 
						 } else if(selected_action === 'zipdl')
						 {
							 window.location.href = window.location.href + "&download=" + response;
						 }
					 }
					 
				 } catch(e)
				 {
					 console.log(e);
				 }
			});
		}
		
	});
	
	jQuery("#tssesl_gps_filter_box_files .tsinf_search_slug_line").not(".filter_box_headline").not(".files_toolbar").click(function() {
		var cb = jQuery(this).find(".tssesl_gps_filter_box_col input[type='checkbox']");
		var row = cb.closest(".tsinf_search_slug_line");
		
		if(cb.is(":checked"))
		{
			cb.removeAttr("checked");
			row.removeClass("selected");
			
		} else {
			cb.prop("checked", "checked");
			row.addClass("selected");
		}
		
		if(cb.hasClass("option_split_by_table"))
		{
			tsinf_db_exp_enable_disable_create_db(cb);
		}
	});
	
	jQuery("#tssesl_gps_filter_box_files .tsinf_search_slug_line").not(".filter_box_headline").not(".files_toolbar").mouseover(function() {
		jQuery(this).addClass("hover");
	});
	
	jQuery("#tssesl_gps_filter_box_files .tsinf_search_slug_line").not(".filter_box_headline").not(".files_toolbar").mouseout(function() {
		jQuery(this).removeClass("hover");
	});
});