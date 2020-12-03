function showHelpModal() {

    $('.help-modal').fadeIn();
    if ($('.help-modal').html() === '') {
        pageLoader();
        $.ajax({
            url : buildLink('help/load'),
            success: function(r) {
                pageLoaded();
                $('.help-modal').html(r);
                reloadInit();
            }
        })
    }
    return false;
}

function closeHelpModal(u) {
    if (u === undefined) {
        $('.help-modal').fadeOut();
    } else {
        pageLoader();
        $.ajax({
            url : u,
            success: function(r) {
                pageLoaded();
                $('.help-modal').html(r);
                reloadInit();
            }
        });
    }
    return false;
}

function doHelpSearch(t) {
    var term = $(t).find('input').val();
    pageLoader();
    $.ajax({
        url : buildLink('help/load', [{key: 'term',value:term}]),
        success: function(r) {
            pageLoaded();
            $('.help-modal').html(r);
            reloadInit();
        }
    });
    return false;
}

function openTutorialContent(id,prev) {
    pageLoader();
    $.ajax({
        url : buildLink('help/content', [{key: 'id',value:id},{key: 'url', value: prev}]),
        success: function(r) {
            pageLoaded();
            $('.help-modal').html(r);
            reloadInit();
        }
    });
    return false;
    return false;
}

function helpPlayVideo(t, id) {
    BigPicture({
        el: t,
        ytSrc: id,
        //dimensions: [1226, 900],
    })
    return false;
}