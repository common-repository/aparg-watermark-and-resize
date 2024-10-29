jQuery(document).ready(function ($) {
    var set_id;
    var limit = 10;
    var loadBar = $("#load_bar");
    var loadPrc = $("#load_pracent");
    var canStop = false;
    clearInterval(set_id);
    set_id = setInterval(apwrCheckStatus, 10000);
      
    /**
     * deleting logo
     */
    $('.delete').on('click', function (e) {
        e.preventDefault();
        $('.show-image').hide();
        $('input[name="logo"]').val("");
        $('#delete-img').val("delete");
    });
    $("#add-img").on("change", function (e) {
        var image_type = e.target.files[0].type;
        var allowedType = ['image/jpeg', 'image/png'];
        if (!($.inArray(image_type, allowedType) > -1)) {
            $('input[type="file"]').addClass('invalid');
        } else {
            $('input[type="file"]').removeClass('invalid');
            $('#delete-img').val("update");
            $('#tempLogo').remove();
            $("#logoImg").remove();
            $('.show-image').show();
            $('.show-image').prepend('<img id="tempLogo" class="logoImg"/>');
            $('#tempLogo').attr('src', URL.createObjectURL(e.target.files[0]));
        }

    });

    /**
     *  open popup
     */

    var modal = $('#ioModal');
    var bulkBtn = $("#bulkBtn");
    var closePopup = $(".close");
    bulkBtn.on('click', function (e) {
        e.preventDefault();
        modal.show();
    });
    closePopup.on('click', function (e) {
        e.preventDefault();
        modal.hide();
    });
    var r = $("#rAll");
    var w = $("#wAll");
    var resize;
    var watermark;
    var ok = $("#okBtn");

    /**
     *  on click ok button send ajax for do action
     */

    ok.on('click', function (e) {
        e.preventDefault();
        $('input,select').each(function () {
            $(this).removeClass('invalid');
        })
        var bulkValid = true;
        apwrClearOptions();
        canStop = false;
        $('#showErrorList').hide();
        $('#showErrorList div').html('');
        modal.hide();
        if (r.is(':checked'))
            resize = r.val();
        else
            resize = '';
        if (w.is(':checked'))
            watermark = w.val();
        else
            watermark = '';
        if (resize || watermark) {
            if (watermark) {
                var checkWtVal = apwrCheckInputVal('global', 'wtm');
                var checkWtm = apwrCheckLogo('global');
                bulkValid = (checkWtVal && checkWtm) ? true : false;
            }
            if (resize) {
                var checkReVal = apwrCheckInputVal('global', 'resize');
                bulkValid = checkReVal ? true : false;

            }
            if (resize && watermark) {
                checkWtVal = apwrCheckInputVal('global', 'wtm');
                checkWtm = apwrCheckLogo('global');
                checkReVal = apwrCheckInputVal('global', 'resize');
                bulkValid = (checkWtVal && checkWtm && checkReVal) ? true : false;
            }
            if (bulkValid) {
                apwrCheckStatus(true);
                apwrBulkOptions.action = resize + '&' + watermark;
            }
        }
    });

    

    /**
     *  stoped action
     */

    var stopBtn = $('#stopBtn');
    stopBtn.on('click', function (e) {
        e.preventDefault();
        $('#stopNotification').show();
        canStop = true;
    });

    /*
     * showing success message
     */
    function apwrShowSuccess() {
        apwrClearOptions();
        apwrClearStatus();
        $(".updated").hide();
        $('#messageSuccess').show();
        $("html, body").animate({scrollTop: 0}, "slow");
    }
    
    /*
     * showing error list
     */
    function apwrShowError(){
        apwrClearOptions();
        apwrClearStatus();
    }
    
    /*
     * close error list
     */
    $('.close-btn').on('click', function (e) {
       e.preventDefault();
        $('#showErrorList').hide();
   });
    
    /*
     * function for clear options
     */
    function apwrClearOptions(){
        $(".meter").hide();
        loadBar.css('width', '0%');
        loadPrc.html('0%');
        $("#save_settings").prop("disabled", false);
        bulkBtn.prop("disabled", false);
        bulkBtn.show();
        stopBtn.hide();
        $('#stopNotification').hide();
        totalCount = 0;
        successCount = 0;
        notDoneCount = 0;
        notDonePath = [];
        percent = 0;
        apwrBulkOptions.lastDoneId = 0;
        $('.meter > span').css('background-color','rgb(30,144,255)');
        clearInterval(set_id);
        set_id = setInterval(apwrCheckStatus, 10000);
    }

    /*
     * disabled bulk button on change form
     */
    var disableBulkBtn = true;
    $("#ioSettingsForm input,form select").on("change", function () {
        bulkBtn.prop("disabled", true);
        disableBulkBtn = false;
    });
  
  /*
   * function for check status for can doing bulk
   */
    var bulkNotification = $('#bulkNotification');
    function apwrCheckStatus(canApwrDoAction) {
        $.ajax({
            type: 'POST',
            url: apwr_optimizer.url,
            dataType: "json",
            data: {
                action: 'apwr_check_status',
                nonce: apwr_optimizer.nonce
            },
            success: function (res) {
                if(res.canStart){
                    if(canApwrDoAction) {
                        apwrDoAction();
                    }
                    if(disableBulkBtn){
                        bulkBtn.prop("disabled", false);
                        bulkNotification.hide();
                    }
                }
                else{
                    bulkBtn.prop("disabled", true);
                    bulkNotification.show();
                }
            },
            error: function () {
                
            }
        });
    }
    
    var errorList = $('#errorList');
    var errorHeader = $('#errorHeader');
    var interlace;
    if ($('#interlace_enable').is(':checked'))
        interlace = $('#interlace_enable').val();
    else
        interlace = null;
    var apwrBulkOptions = {
        maxWidth: $('#max_width').val() ? $('#max_width').val() : '',
        maxHeight: $('#max_height').val() ? $('#max_height').val() : '', 
        imgQuality: $('#img_quality').val() ? $('#img_quality').val() : '',
        watermarkPosition: $('#watermark_position').val() ? $('#watermark_position').val() : '',
        watermarkPercentage: $('#watermark_percentage').val() ? $('#watermark_percentage').val() : '',
        watermarkMargin: $('#watermark_margin').val() ? $('#watermark_margin').val() : '',
        watermarkPath: $('#logoImg').data('path') ? $('#logoImg').data('path') : '',
        interlace: interlace,
        action: '',
        limit: limit,
        lastDoneId: 0
    };
    var totalCount = 0;
    var successCount = 0;
    var notDoneCount = 0;
    var notDonePath = [];
    var percent = 0;
    
    /*
     * function for do action and showing percentage
     */
    function apwrDoAction() {
        clearInterval(set_id);
        $('#serverNotification').hide();
        $(".meter").show();
        $("#save_settings").prop("disabled", true);
        bulkBtn.prop("disabled", true);
        bulkBtn.hide();
        stopBtn.show();
        $.ajax({
            type: 'POST',
            url: apwr_optimizer.url,
            dataType: "json",
            data: {
                action: 'apwr_change_all_img',
                apwrBulkOptions: apwrBulkOptions,
                nonce: apwr_optimizer.nonce
            },
            timeout: 60000,
            success: function (res) {
                if(res && !canStop){
                    if(res.total == 0){
                        loadBar.css('width', 100 + '%');
                        loadPrc.html(100 + '%');
                        stopBtn.prop('disabled',true);
                        if(notDoneCount == 0)
                            setTimeout(apwrShowSuccess, 2000);
                        else
                            apwrShowError();
                    }
                    else {
                        if(res.total > totalCount)
                            totalCount = res.total;
                        successCount = successCount + (res.current - res.notDone);
                        if(res.notDone > 0){
                            $('#showErrorList').show();
                            errorHeader.html('<p>' + apwr_optimizer.errPathList + '</p>');
                            for(var i = 0; i < res.notDone; i++){
                                notDonePath.push(res.notDonePath[i]);
                            }
                            for(var j = notDoneCount; j < notDonePath.length; j++){
                                var errorPath = '<div>'+ notDonePath[j] +'</div>';
                                errorList.append(errorPath);
                            }
                        }
                        notDoneCount = notDoneCount + res.notDone;
                        percent = Math.round((successCount * 100) / totalCount);
                        if(percent < 100){
                            loadBar.css('width', percent + '%');
                            loadPrc.html(percent + '%');
                        }
                        else{
                            loadBar.css('width', 100 + '%');
                            loadPrc.html(100 + '%');
                            stopBtn.prop('disabled',true);
                            if((successCount >= totalCount) && (notDoneCount == 0))
                                setTimeout(apwrShowSuccess, 2000);
                            else
                                apwrShowError();
                        }    
                        if(totalCount > successCount){
                            apwrBulkOptions.lastDoneId = res.lastDoneId;
                            apwrDoAction();
                        }
                    }    
                }
                if(canStop){
                    if(notDoneCount == 0)
                        apwrShowSuccess();
                    else
                        apwrShowError();
                }
            },
            error: function () {
                $('.meter > span').css('background-color','#dd3d36');
                $('#serverNotification').show();
                $("#save_settings").prop("disabled", false);
                bulkBtn.prop("disabled", false);
                bulkBtn.show();
                stopBtn.hide();
                apwrClearStatus();
            }
        });
    }
    
    /*
     * function for clear status in basa
     */
    
    function apwrClearStatus() {
        $.ajax({
            type: 'POST',
            url: apwr_optimizer.url,
            dataType: "json",
            data: {
                action: 'apwr_clear_status',
                nonce: apwr_optimizer.nonce
            },
            success: function () {
                
            },
            error: function () {
               
            }
        });
    }
    
    
    /*
     *      Validate form functinons  
     */

    $('#save_settings').click(function () {
        var valid = true;

        if (apwrCheckLogo() == false) {
            valid = false;
        }
        if (apwrCheckInputVal() == false) {
            valid = false;
        }
        
        if (!valid) {
            return false;
        }

    });


    /*
     * function for validate position and  logo 
     */
    function apwrCheckLogo(bulk) {
        bulk = bulk || 'onfly';
        $('input[type="file"]').removeClass('invalid');
         $('#watermark_position').removeClass('invalid');
        var validator = true;
        var file = $('input[type="file"]').val();
        var exts = ['jpg', 'png', 'jpeg'];
        var wtmCheckbox = $('input[name="watermark_enable"]').is(":checked");
        if (bulk == 'global') {
            wtmCheckbox = true;
        }
        var wtmPosition = $('#watermark_position').val();
        if (file) {
            var get_ext = file.split('.');
            get_ext = get_ext.reverse();
            if (!($.inArray(get_ext[0].toLowerCase(), exts) > -1)) {
                $('input[type="file"]').addClass('invalid');
                validator = false;
            } else {
                $('input[type="file"]').removeClass('invalid');
            }
        }
        if (wtmCheckbox) {

            if (($('.show-image').css('display') == 'none') || !($('.logoImg').length > 0)) {
                $('input[type="file"]').addClass('invalid');
                validator = false;
            } else {
                $('input[type="file"]').removeClass('invalid');
            }
            if (!wtmPosition) {
                $('#watermark_position').addClass('invalid');
                validator = false;
            } else {
                $('#watermark_position').removeClass('invalid');
            }

        }
        return validator;
    }
    /*
     * function for validate input values
     * @param {type} bulk
     * @returns {Boolean}
     * 
     */
    function apwrCheckInputVal(bulk, action) {
        var check = true;
        bulk = bulk || 'onfly';
        action = action || 'empty';
        var parentTable = 'table';
        if (action == 'resize') {
            parentTable = '#form-table-resize';
        }
        if (action == 'wtm') {
            parentTable = '#form-table-wtm';
        }

        $("input[type='checkbox']").parents(parentTable).find('input[type="text"]').each(function () {
            var value = $(this).val(), checked, result;

            checked = $(this).parents('table').find("input[type='checkbox']").is(":checked") ? true : false;
            if (bulk == 'global') {
                checked = true;
            }
            if ($(this).hasClass('number')) {
                if(($('#max_height').val() == '') && ($('#max_width').val() == '') && checked)
                    checked = true;
                else{
                    if(value == '')
                        checked = false;
                }
                result = apwrCheckNum(value, checked);
            }

            if ($(this).hasClass('precent')) {
                if($(this).hasClass('quality')){
                    checked = false;
                }    
                result = apwrCheckPracent(value, checked);
            }
            
            if ($(this).hasClass('margin')) {
                if(value == '')
                    checked = false;
                result = apwrCheckMargin(value, checked);
            }
            
            if (!result) {
                $(this).addClass('invalid');
                check = false;
            } else {
                $(this).removeClass('invalid');
            }
        });
        return check;
    }

    function apwrCheckMargin(val, checked) {
        if (checked) {
            return !val.match(/^(?:[0-9]\d?|100)$/g) ? false : true;
        } else {
            return !val || val.match(/^(?:[0-9]\d?|100)$/g) ? true : false;
        }
    }

    function apwrCheckPracent(val, checked) {
        if (checked) {
            return !val.match(/^(?:[1-9]\d?|100)$/g) ? false : true;
        } else {
            return !val || val.match(/^(?:[1-9]\d?|100)$/g) ? true : false;
        }
    }


    function apwrCheckNum(val, checked) {
        if (checked) {
            return  !val.match(/^[1-9]\d*$/g) ? false : true;

        } else {
            return !val || val.match(/^[1-9]\d*$/g) ? true : false;

        }
    }
    /*
     * checking GD library for watermark
     */
    if(!apwr_optimizer.wtmEnable){
       $('#form-table-wtm').addClass('disable').css({opacity:'0.5',position:'relative'}); 
      $('.delete').off('click');
      $('#wAll').attr("disabled", true);
      $('#wtm_header').append('<span class="gdErrorMsg">'+apwr_optimizer.wtmEnableMessage +'</span>')
     
    }
});




