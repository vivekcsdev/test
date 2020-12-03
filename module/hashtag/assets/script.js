function loadHashtags() {
    var c = $(".hashtag-list");
    if (c.html() === '') {
        c.html("<div class='loader-image'><img src='"+loaderImage+"'/></div>");
        $.ajax({
            url: buildLink('hashtags', [{key: 'load', value: 1}]),
            success: function(data) {
                c.html(data);
            }
        })
    }
    c.parent().fadeIn();
    return false;
}

function useHashtag(t) {
    var el = $(".post-text textarea").emojioneArea();
    el[0].emojioneArea.setText(el[0].emojioneArea.getText()+' ' +$(t).find('.value').html());
    closeLoadHashtags();
    return false;
}

function closeLoadHashtags() {
    $('.use-hashtag-container').fadeOut();
    return false;
}