jQuery(document).ready(function(){
    jQuery(".tsinf_comfortdb_plugin_edit_dataset_options .tsinf_comfortdb_unlock_item").click(function(){
        if(jQuery(this).hasClass("locked"))
        {
            jQuery(this).closest(".tsinf_comfortdb_plugin_edit_dataset_row").find(".tsinf_comfortdb_plugin_edit_dataset_edit_field").prop("readonly", false);
            jQuery(this).removeClass("locked");
            jQuery(this).addClass("unlocked");
            var button_text = jQuery(this).data("unlockedtxt");
            jQuery(this).text(button_text);
        } else {
            jQuery(this).closest(".tsinf_comfortdb_plugin_edit_dataset_row").find(".tsinf_comfortdb_plugin_edit_dataset_edit_field").prop("readonly", true);
            jQuery(this).removeClass("unlocked");
            jQuery(this).addClass("locked");
            var button_text = jQuery(this).data("lockedtxt");
            jQuery(this).text(button_text);
        }
    });
    
    
    jQuery(".tsinf_comfortdb_plugin_edit_dataset_options .tsinf_comfortdb_plugin_edit_dataset_is_null_checkbox").click(function(){
    	if(jQuery(this).is(":checked"))
		{
    		jQuery(this).closest(".tsinf_comfortdb_plugin_edit_dataset_row").find(".tsinf_comfortdb_plugin_edit_dataset_edit_field").prop("disabled", true);
		} else {
			jQuery(this).closest(".tsinf_comfortdb_plugin_edit_dataset_row").find(".tsinf_comfortdb_plugin_edit_dataset_edit_field").prop("disabled", false);
			jQuery(this).closest(".tsinf_comfortdb_plugin_edit_dataset_row").find(".tsinf_comfortdb_plugin_edit_dataset_edit_field").focus();
		}
    });
});