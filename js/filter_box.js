jQuery(document).ready(function() {
	jQuery(".tssesl_gps_filter_box .tsinf_check_all").click(function() {
		var _this = jQuery(this);
		var _this_col = _this.closest(".tssesl_gps_filter_box_col");
		var _this_col_count = _this_col.parent().children().index(_this_col);
				
		var _lines = _this.closest(".tssesl_gps_filter_box").find(".tsinf_search_slug_line");
		
		_lines.each(function() {
			var _target = jQuery(this).find(".tssesl_gps_filter_box_col").eq(_this_col_count).find("input");
			if(_this.is(":checked"))
			{
				_target.prop("checked", "checked");
			} else {
				_target.removeAttr("checked");
			}
		});
	});
});	


