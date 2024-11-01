jQuery(document).ready(function(){
    var table = jQuery(".tsinf_comfortdb_table");
    var meta_fields = jQuery(".tsinf_comfortdb_table .tsinf_comfortdb_column_metadata.data");
    if(meta_fields.length)
    {
        var table = table.data("tablename");
        
        meta_fields.each(function(){
            var column = jQuery(this).data("metacol");
            var auth = jQuery(this).data('metacol-auth');

            var data = {
				'action': 'get_column_meta_data',
				'table': table,
                'column': column,
                'security': auth
			};
		      
            var current_element = jQuery(this);
            jQuery.post(ajaxurl, data, function(response) {
                var meta_info = jQuery.parseJSON(response);
                current_element.empty();
                current_element.append("<span class='meta_info datatype'>" + meta_info.type + "</span>");
                
                if(meta_info.is_primary_key === true)
                {
                    current_element.append("<span class='meta_info primary_key'>" + TSINF_COMFORT_DB_TABLE_AJAX_JS.primary_key + "</span>");
                }
                
                if(meta_info.is_foreign_key === true)
                {
                    var foreign_key_element = jQuery("<span class='meta_info foreign_key'>"+ TSINF_COMFORT_DB_TABLE_AJAX_JS.foreign_key + "</span>");
                    foreign_key_element.click(function(){
                        jQuery(this).closest(".tsinf_comfortdb_column_metadata").find(".foreign_key_details").toggle();
                    });
                    
                    current_element.append(foreign_key_element);
                    
                    if(meta_info.foreign_key_relations.length > 0)
                    {
                        var foreign_key_infos = "<div class='meta_info foreign_key_details'>";
                                                
                        meta_info.foreign_key_relations.forEach(function(item, index)
                        {
                            foreign_key_infos += "<span class='fkdetail_item'>" + TSINF_COMFORT_DB_TABLE_AJAX_JS.references + " <span class='fkdetail_item_table'>" + 
                                item.REFERENCED_TABLE_NAME + 
                                "(<span class='fkdetail_item_column'>" + 
                                item.REFERENCED_COLUMN_NAME + 
                                "</span>)</span>";
                        });
                        foreign_key_infos += "</div>";
                        current_element.append(foreign_key_infos);
                    }
                }
            });
        });
    }
});