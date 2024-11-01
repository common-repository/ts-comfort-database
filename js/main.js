jQuery(document).ready(function(){
    jQuery(".tsinf_select_menu .tsinf_select_menu_label_wrapper").click(function(){
        jQuery(this).closest(".tsinf_select_menu").find(".tsinf_select_menu_options_wrap").slideToggle();
    });
    
     jQuery(".tsinf_select_symbol_button").click(function(){
        var $el = jQuery(this);
        jQuery(this).find(".tsinf_select_menu_options_wrap").slideToggle('slow', function(){
            if($el.find(".tsinf_select_menu_options_wrap").is(":visible"))
            {
                $el.find(".arrow-up").show();
            } else {
                $el.find(".arrow-up").hide();
            }
        });
    });
    
    jQuery("#tsinf_comfortdb_risk_message_button").click(function(){
        var _this = jQuery(this);
        var auth = jQuery(this).data('riskconfirm-auth');
        var data = {
            'action': 'risk_message_button_confirmed',
            'security': auth
        };
        
        jQuery.post(ajaxurl, data, function(response) {
            var message_confirmed = parseInt(response);
            if(message_confirmed)
            {
                _this.closest(".notice.notice-error").fadeOut();
            }
        });
    });
    
    jQuery(".tsinf_comfortdb_tablename_filter").keyup(function(){
        var input = jQuery(this);
        var filter = jQuery.trim(input.val().toUpperCase());
        var table_body = input.closest(".tsinf_comfortdb_table_data_wrapper").find("tbody");
        var tr = table_body.find(".tsinf_comfortdb_table_overview_row");
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
});

function tsinf_comfort_db_create_dialog(headertext, content)
{
    var dialog_str = '<div class="tsinf_comfortdb_dialog">' + 
            '<div class="tsinf_comfortdb_dialog_header">' +
                headertext
            '</div>' +
            '<div class="tsinf_comfortdb_dialog_content">' +
            content +        
            '</div>' +
            '<div class="tsinf_comfortdb_dialog_footer">' +
                '<button class="tsinf_comfortdb_dialog_footer_abort">Abbrechen</button>' +
                '<button class="tsinf_comfortdb_dialog_footer_submit">Übernehmen</button>' +
            '</div>' +
        '</div>';
    
    return jQuery(dialog_str);
}