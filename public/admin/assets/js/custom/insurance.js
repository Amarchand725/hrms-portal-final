$(document).on('change', '#marital_status, #sex', function(){
    var marital_status = $('#marital_status').val();
    var sex = $('#sex').val();
    var html = '';
    if(marital_status==1 && sex==1){
        html = '<option value="" selected>Select relation</option>'+
                '<option value="wife">Wife</option>'+
                '<option value="son">Son</option>'+
                '<option value="daughter">Daughter</option>';
    }else if(marital_status==1 && sex==0){
        html = '<option value="" selected>Select relation</option>'+
                '<option value="husband">Husband</option>'+
                '<option value="son">Son</option>'+
                '<option value="daughter">Daughter</option>';
    }else if(marital_status != ''){
        html = '<option value="" selected>Select relation</option>'+
                '<option value="father">Father</option>'+
                '<option value="mother">Mother</option>';
    }
    
    $('.relationships').html(html);
});
$(document).on('change', '.relationships', function(){
    var relation = $(this).val();
    if(relation=='father'){
        var html = '';
        html =  '<label class="form-label" for="father_cnic_number">CNIC <span class="text-danger">*</span></label>'+
                '<input type="text" id="father_cnic_number" name="father_cnic_number" class="form-control cnic_number" required placeholder="Enter father cnic number "/>'+
                '<div class="fv-plugins-message-container invalid-feedback"></div>'+
                '<span id="father_cnic_number_error" class="text-danger error"></span>';
        $(this).parents('.relation_data').find('.cnic').html(html);
    }else{
        $(this).parents('.relation_data').find('.cnic').html('');
    }
});
$(document).on('click', '.add-more-btn', function(){
    var marital_status = $('#marital_status').val();
    var rel_html = '';
    if(marital_status==1){
        rel_html = '<option value="" selected>Select relation</option>'+
                '<option value="husband">Husband</option>'+
                '<option value="wife">Wife</option>'+
                '<option value="son">Son</option>'+
                '<option value="daughter">Daughter</option>';
    }else{
        rel_html = '<option value="" selected>Select relation</option>'+
                '<option value="father">Father</option>'+
                '<option value="mother">Mother</option>';
    }

    var html = '';
    html = '<span class="relation_data">'+
                '<div class="row mt-4 w-full border-top py-2 position-relative">'+
                    '<div class="close-btn-wrapper position-absolute d-flex flex-row-reverse mb-3">'+
                        '<button type="button" class="btn btn-label-primary btn-sm btn-relation-close"><i class="fa fa-close icon-close"></i></button>'+
                    '</div>' +
                    '<div class="col-md-6">'+
                        '<label class="form-label" for="relationships">Relationship </label>'+
                        '<select class="form-control relationships" id="relationships" name="relationships[]">'+
                            rel_html +
                        '</select>'+
                        '<div class="fv-plugins-message-container invalid-feedback"></div>'+
                        '<span id="relationships_error" class="text-danger error"></span>'+
                    '</div>'+
                    '<div class="col-md-6">'+
                        '<label class="form-label" for="family_rel_names">Name <span class="text-danger">*</span></label>'+
                        '<input type="text" id="family_rel_names" name="family_rel_names[]" class="form-control" placeholder="Enter name" />'+
                        '<div class="fv-plugins-message-container invalid-feedback"></div>'+
                        '<span id="family_rel_names_error" class="text-danger error"></span>'+
                    '</div>'+
                '</div>'+
                '<div class="row mt-3">'+
                    '<div class="col-md-6">'+
                        '<label class="form-label" for="family_rel_dobs">Date of birth </label>'+
                        '<input type="date" id="family_rel_dobs" name="family_rel_dobs[]" class="form-control" />'+
                        '<div class="fv-plugins-message-container invalid-feedback"></div>'+
                        '<span id="family_rel_dobs_error" class="text-danger error"></span>'+
                    '</div>'+
                    '<div class="col-md-6 cnic"></div>'+
                '</div>'+
            '</span>';

            $('#add-more-data').append(html);
});

$(document).on('click', '.btn-relation-close', function(){
    $(this).parents('.relation_data').remove();
});

$(document).on('keyup', '#father_cnic_number', function() {
    // Get the input value
    var cnic = $(this).val();
    
    if (cnic.length < 15) {
        // Display an error message if the input doesn't match the pattern
        $('#father_cnic_number_error').text('Father cnic length is not correct.');
    } else {
        // Clear the error message if the input is valid
        $('#father_cnic_number_error').text('');
    }
});