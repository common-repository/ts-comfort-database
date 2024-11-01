function tsinf_comfort_db_post_search_row_template(obj, even_odd, post_url)
{
	try {
		post_url = post_url.replace("###ID###", obj.ID);
	} catch(e)
	{
		post_url = '#';
	}
	
	var str = '<tr class="' + even_odd + '">' +
						'<td class="col_data_id">' + obj.ID + '</td>' +
						'<td class="col_data_author">' + obj.post_author + '</td>' +
						'<td class="col_data_title"><a href="' + post_url + '" target="_blank">' + obj.post_title + '</a></td>' +
						'<td class="col_data_status">' + obj.post_status + '</td>' +
						'<td class="col_data_postname">' + obj.post_name + '</td>' +
						'<td class="col_data_parent">' + obj.post_parent + '</td>' +
						'<td class="col_data_post_type">' + obj.post_type + '</td>' +
						'<td class="col_data_comment_count">' + obj.comment_count + '</td>' +
						'<td class="col_data_created">' +
							obj.post_date + '<hr>' +
							obj.post_date_gmt + ' (GMT)' +
						'</td>' +
						'<td class="col_data_modified">' +
							obj.post_modified + '<hr>' +
							obj.post_modified_gmt + ' (GMT)' +
						'</td>' +
					'</tr>';
	
	return str;
}

jQuery(document).ready(function() {
	
	jQuery(".tsinf_ps_load_more_container .tsinf_ps_load_more_button").click(function() {
		var button_load_more = jQuery(this);
		var form = jQuery("#tsinf_comfort_db_global_search_form");
		var result_table = button_load_more.closest("#ts_comfortdb_global_post_search_results_main");
		var result_table_body = result_table.find("tbody");
		var rows = result_table.find("tbody > tr");
		
		var auth = form.data("auth");
		
		var form_data = form.serialize();
				
		var offset = rows.length;
		
		var data = {
			'action': 'tsinf_ps_get_data',
			'rq': form_data,
			'offset': offset,
			'security': auth
		};
		
		button_load_more.addClass("tsinf_loading_button");
		button_load_more.prop("disabled", true);
		
		jQuery.post(ajaxurl, data, function(response) {			
			try {
				if(response.length > 0)
				{
					var row_count = offset + 1;
					jQuery.each(response, function(k,v) {
						var even_odd = 'odd';
						if(row_count % 2 === 0)
						{
							even_odd = 'even';
						}
						
						var result_str = tsinf_comfort_db_post_search_row_template(v, even_odd, result_table.data('admin-url'));
						result_table_body.append(result_str);
					});
				} else {
					button_load_more.fadeOut();
				}
				
			} catch(e)
			{
			}
			
			button_load_more.removeClass("tsinf_loading_button");
			button_load_more.prop("disabled", false);
		});
	
	});
	
});


