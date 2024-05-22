jQuery(document).ready(function($) {
    $('#send_otp_button').click(function() {
        var phone = $('#phone_number').val();
        $.post(otp_ajax_object.ajax_url, {
            action: 'send_otp',
            phone: phone
        }, function(response) {
            alert(response.data.message);
        });
    });

    $('#verify_otp_button').click(function() {
        var phone = $('#phone_number').val();
        var otp = $('#otp').val();
        $.post(otp_ajax_object.ajax_url, {
            action: 'verify_otp',
            phone: phone,
            otp: otp
        }, function(response) {
            alert(response.data.message);
        });
    });
});
