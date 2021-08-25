function tm_ns_validateForm() {
  var isValid = true;
  jQuery('#ns_general_settings_form input').each(function() {
    if ( jQuery(this).val() === '' )
        isValid = false;
  });
  return isValid;
}

jQuery(document).ready(function ($) {
    $("#ns_promo_custform_id").attr('placeholder', 'NS Promo Custom Form ID' );
    $("#ns_promo_discount_id").attr('placeholder', 'NS Promo Discount ID' );
    // $("#price_level_name").attr('placeholder', 'Price Level Name' );


        makeSelectfieldsSelect2($);
        $('#ns_order_shiping_line_item_enable').click(function(){
            if($('#ns_order_shiping_line_item_enable:checked').length > 0){
                // $('#ns_order_shiping_line_item').css('display','');
                $('#ns_order_shiping_line_item').removeClass('ns_order_shiping_line_item');
            }else{
                $('#ns_order_shiping_line_item').addClass('ns_order_shiping_line_item');
                // $('#ns_order_shiping_line_item').css('display','none');
            }
        });

        // $('#ns_order_shiping_line_item_enable').click(function(){
        //     if($('#ns_order_shiping_line_item_enable:checked').length > 0){
        //         $('#ns_order_shiping_line_item').removeClass('ns_order_shiping_line_item');
        //         // $('#ns_order_shiping_line_item').css('display','');
        //     }else{
        //         $('#ns_order_shiping_line_item').addClass('ns_order_shiping_line_item');
        //         // $('#ns_order_shiping_line_item').css('display','none');
        //     }
        // });


        $("input:radio[name='order_item_location']").on('change',function(){
            console.log($(this).val());
            if($(this).val() == 3){
                // $(".hidden_tr_input").show();
                $("#hidden_tr_input").removeClass('hidden_tr_input');
                $("input[name='order_item_location_value']").focus();
                $("input[name='order_item_location_value']").prop('required',true);

            }else{
                $("input[name='order_item_location_value']").prop('required',false);
                 $("#hidden_tr_input").addClass('hidden_tr_input');

                 // $(".hidden_tr_input").hide();
            }
        });
        
        $('#ns_coupon_netsuite_sync').click(function(){
            if($('#ns_coupon_netsuite_sync:checked').length > 0){
                // $('#promo_required_fields').css('display','');
                $( "#promo_required_fields" ).removeClass( "promo_required_fields" );

            }else{
                // $('#promo_required_fields').css('display','none');
                  $( "#promo_required_fields" ).addClass( "promo_required_fields" );

            }
        });


        // $('#enablePriceSync').click(function(){
        //     if($('#enablePriceSync:checked').length > 0){
        //         $("input[name='price_level_name']").prop('required',true);
        //     }else{
        //         $("input[name='price_level_name']").prop('required',false);
        //     }
        // });
        
        
        
    });


jQuery(function ($) {
    $("#test_api_creds").on('click', function(){
            $(this).attr('disabled', true);
            if(tm_ns_validateForm()) {
                var data = {'action':'tm_validate_ns_credentials'};
                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: tmwni_admin_settings_js.ajax_url,
                    data: {'action':'tm_validate_ns_credentials',nonce : tmwni_admin_settings_js.nonce,},
                    success: function (response) {
                         $("#test_api_creds").attr('disabled', false);
                         alert(response.message);
                    },
                    complete: function(x) {
                         $("#test_api_creds").attr('disabled', false);
                    }
                });
            } else {
                alert("Please 'enter & save' API credentials first");
            }
        });
    
    $(document).on('submit', '.tm_netsuite_ajax_form_save', function (e) {
        e.preventDefault();
        $('#loader-wrapper').show();
        var data = $(this).serialize();
        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: data,
            success: function (response) {
                $("head").append('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">');
                $('#loader-wrapper').hide();
                if (response.type == "success"){
                    $.notify(" Settings have been successfully saved", {type: "success", icon:"check",align:"right"});
                    $('.tm_netsuite_ajax_form_save').find('input,select').each(function () {
                        $(this).css('border','');
                    });
                }else if(response.type == "blankfield"){
                    $.notify(' '+response.msg, {type: "danger", icon:"exclamation", align:"right"});
                    $('.tm_netsuite_ajax_form_save').find('input,select').each(function () {
                        $(this).css('border','');
                        $(this).next('.select2-container').find(".select2-selection").css('border','');
                        if ($.trim($(this).val()) == '' || $.trim($(this).val()) == 0 ) {
                            if($.trim($(this).attr('name')).indexOf('wc_field_value_prefix') == -1){
                                isValid = false;
                                $(this).css('border','1px solid red');
                            }
                            if($(this).hasClass("select2-hidden-accessible")){
                                $(this).next('.select2-container').find(".select2-selection").css('border','1px solid red');
                            }
                        }
                    });
                }else if(response.type == "Error"){
                    $.notify(' '+response.msg, {type: "danger", icon:"close", align:"right",color: "#fff", background: "#D44950"});
                }else{
                    $.notify(' '+response.msg, {type: "danger", icon:"close", align:"right",color: "#fff", background: "#D44950"});
                }
            }
        });

        return false;
    });
});


(function ($) {
    $(document).on('change', ".cm_operator", function () {
        var cm_operator = $(this).val();
        if (cm_operator != 0) {
            var this_table = $(this).closest('table');
            var cm_box_index = this_table.parent().data('index');
            if (cm_operator == 2) {
                this_table.find('tbody tr').eq(0).find('td:gt(0)').remove();
                processCM(1, 2, this_table);
            } else if (cm_operator == 1 || cm_operator == 3) {
                this_table.find('tbody tr:gt(0)').remove();

                getCMTypeDropdown(this_table, cm_box_index);
            }
        }
        makeSelectfieldsSelect2($);
    });

    $(document).on('change', ".cm_type", function () {
        var cm_type = $(this).val();
        if (cm_type != 0) {
            var cm_operator = $(this).closest('td').prev('td').find('.cm_operator').val();
            if (cm_operator != 0) {
                var this_table = $(this).closest('table');
                processCM(cm_type, cm_operator, this_table);
            }
        }
        makeSelectfieldsSelect2($);
    });

    $(document).on('change', ".attr_type", function () {
        var attr_type = $(this).val();
        
        if (attr_type != 0) {
            var cm_operator = $(this).closest('.netsuite_conditional_mapping').find('.cm_operator').val();
            var cm_type = $(this).closest('.netsuite_conditional_mapping').find('.cm_type').val();

            var cm_condition = cm_type != null ? getConditionVal($(this),cm_type) : [];

            if (cm_operator != 0) {
                var this_table = $(this).closest('table');
                processCM(cm_type, cm_operator, this_table, attr_type, cm_condition);
            }
        }
        makeSelectfieldsSelect2($);
    });

    $(document).on('click', ".removeCMRow", function () {
        if (confirm("This action can't be undone")) {
            //truncating first row instead of removing
            if ($('.netsuite_conditional_mapping').length == 1) {

                //reset input and select
                $('.netsuite_conditional_mapping').find('select.cm_type').remove();

                $('.netsuite_conditional_mapping tbody tr:gt(0)').remove();

                $('.netsuite_conditional_mapping tbody tr td').eq(1).empty();

                $('.netsuite_conditional_mapping').find('select.cm_operator').each(function () {
                    $(this).val(0);
                });
            } else {
                $(this).parent().remove();
            }
        }
    });

    $(document).on('click', "#addMoreConditionalMappingRows", function () {

        var clone_this = $(this).prev('div'); //get prev table to clone

        var current_index = clone_this[0].dataset.index; //get current index
        var next = parseInt(current_index) + 1; //increment table index   

        clone_this[0].dataset.index = next; //increment table index
        clone_this.find('tbody .cm_operator').attr('name', 'cm[' + next + '][operator]');
        $(this).before(clone_this.clone()); // add new mapping row by cloing prev table

        clone_this[0].dataset.index = current_index; //fix for prev table
        clone_this.find('tbody .cm_operator').attr('name', 'cm[' + current_index + '][operator]');

        //reset input and select
        $('.netsuite_conditional_mapping:last tbody tr:gt(0)').remove();

        $('.netsuite_conditional_mapping:last tbody tr').eq(0).find('td').eq(1).empty();
        
        if ($('.netsuite_conditional_mapping:last').prev('.requiredCM').length == 1) {
            $('.netsuite_conditional_mapping:last').prev('.requiredCM').replaceWith("<span style='float:right;' class='btn btn-danger removeCMRow'>X</span>");
        }
        
        $('.netsuite_conditional_mapping:last').parent().find('.update_checkbox').attr('checked', false);

        $('.netsuite_conditional_mapping:last').parent().find('.update_checkbox').attr('name', 'cm['+next+'][exlcude_in_update]');
        
        $('.netsuite_conditional_mapping:last').find('select').each(function () {
            $(this).val(0);
        });
    });

    $(document).on('change','.ns-field-key',function(){
        var $this = $(this);
        $this.parents('tr').find('input.ns-field-value').attr('placeholder','');
        $this.parents('tr').find('input.ns-field-type').val($('option:selected', $this).attr('data-type'));
        if($('option:selected', $this).attr('data-type') == 'dateTime'){
            $this.parents('tr').find('input.ns-field-value').attr('type','datetime-local');
        }else{
            $this.parents('tr').find('input.ns-field-value').attr('type','text');
        }

        if( $('option:selected', $this).attr('data-type') == "boolean" ){
            $this.parents('tr').find('input.ns-field-value').attr('placeholder','True or False only');
            $this.parents('tr').find('input.ns-field-value').attr('value', "");
        }

    });

    $(document).on('change','.ns-field-type',function(){
        var $this = $(this);
        $this.parents('tr').find('input.ns-field-value').attr('placeholder','');
        if($this.val() == 'dateTime'){
            $this.parents('tr').find('input.ns-field-value').attr('type','datetime-local');
        }else if($this.val() == 'customboolean'){
            $this.parents('tr').find('input.ns-field-value').attr('placeholder','True or False only');
            $this.parents('tr').find('input.ns-field-value').attr('value','');
        }else{
            $this.parents('tr').find('input.ns-field-value').attr('type','text');
        }

    });

    $(document).on('change','.wc_fieldkey',function(){
        var $this = $(this);
        $this.closest('.netsuite_conditional_mapping').find('[name$="[wc_field_value]"]').attr('placeholder','');
        
        if($this.val() == "country" || $this.val() == "state" || $this.val() == "order_currency" || $this.val() == "billing_country" || $this.val() == "billing_state" || $this.val() == "shipping_state" || $this.val() == "shipping_country"){
            $this.closest('.netsuite_conditional_mapping').find('[name$="[wc_field_value]"]').attr('placeholder','ISO alpha-2 code only.');
            $this.closest('.netsuite_conditional_mapping').find('[name$="[wc_field_value]"]').attr('value','');
        }

    });

    
})(jQuery);

function processCM(cm_type, cm_operator, this_table, attr_type="" , cm_condition = []) {
    var index = this_table.parent('div').data('index');
    // if (this_table.find("tbody tr").length > 2 && !confirm("This action can't be undone")) {
    //     return false;
    // }
    this_table.find("tr:gt(0)").remove();
    var wcfieldkey ="";
    var template = getCMTemplate(cm_type, cm_operator, index,wcfieldkey, attr_type, cm_condition );
    if (template !== false) {
        this_table.find("tbody").append(template);
    }
}

//get conditional mapping template based on type and operator
function getCMTemplate(type=0, operator, index, wcfieldkey="",attr_type="" , cm_condition=[]) {
    // $conditional_mapping_nonce = wp_create_nonce('conditional-mapping-nonce');
    var html_template = '';
    jQuery.ajax({
        url: ajaxurl,
        dataType: 'json',
        type: 'POST',
        async: false,
        data: {
            'action': 'get_conditional_mapping_template',
            'type': type,
            'operator': operator,
            'index': index,
            'attr_type': attr_type,
            'cm_wc_field_key': cm_condition['wc_field_key'] != null ? cm_condition['wc_field_key'] : "",
            'cm_wc_field_value': cm_condition['wc_field_value'] != null ? cm_condition['wc_field_value'] : "",
            'cm_wc_where_op': cm_condition['wc_where_op'] != null ? cm_condition['wc_where_op'] : "",
            'tab' : getUrlParameter('tab'),
            'nonce': tmwni_admin_settings_js.nonce,

        },
        success: function (data) {
            if (data.status == 1) {
                html_template = data.template;
            }
        }
    });

    return html_template;
}

function getCMTypeDropdown(this_table, cm_box_index) {
    var template = '<td><span class="h5">WC Field Source</span><br/><select class="cm_type input-sm" name="cm[' + cm_box_index + '][type]"><option value="0" selected>-- select field type --</option><option value="1">Customer Field</option><option value="2">Customer Meta Field</option><option value="3">Order Field</option><option value="4">Order Meta Field</option></select></td>';
    this_table.find('tbody tr').eq(0).find('td:gt(0)').remove();
    this_table.find('tbody tr').eq(0).append(template);
}

function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};


function getConditionVal($this,cm_type){
    var conditions = [];
    conditions['wc_field_key'] = $this.closest('.netsuite_conditional_mapping').find('[name$="[wc_field_key]"]').val();
    conditions['wc_field_value'] = $this.closest('.netsuite_conditional_mapping').find('[name$="[wc_field_value]"]').val();
    conditions['wc_where_op'] = $this.closest('.netsuite_conditional_mapping').find('[name$="[wc_where_op]"]').val();
    return conditions;
}


function makeSelectfieldsSelect2($){
    $('select.ns-field-key').each(function (i, obj) {
        if (!$(obj).data('select2'))
        {
            $(obj).select2();
        }
    }); 

    $('select[name*="wc_field_key"]').each(function (i, obj) {
        if (!$(obj).data('select2'))
        {
            $(obj).select2();
        }
    }); 
}