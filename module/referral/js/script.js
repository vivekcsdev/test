function referralPayoutSettings(t) {
    var v = $(t).val();
    if (v === '0') {
        $('.referral-paypal-email').hide();
        $('.referral-paypal-email').find('input').removeAttr('required')
    } else {
        $('.referral-paypal-email').fadeIn();
        $('.referral-paypal-email').find('input').attr('required', 'required')
    }
}

function referralCopy(id) {
    var copyText = document.getElementById(id);
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($('#'+id).val()).select();
    document.execCommand("copy", false, $temp.val());
    $temp.remove();
    return false;
}

function referralShowBanner(t, r){
    $('.each-banner').hide();
    $('.each-banner-'+r).fadeIn();
    $('.referral-banner-container li a').removeClass('colored-re');
    $(t).addClass('colored-re');
    return false;
}