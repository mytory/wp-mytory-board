jQuery(function ($) {
    var $DomHasPostId = $('[data-post-id]');
    if (MytoryBoard.wpDebug && $DomHasPostId.length == 0 && typeof window.console == 'object') {
        console.log("data-post-id='1234' 형식의 어트리뷰트를 가진 HTML 요소가 없습니다. 페이지뷰를 올리지 않습니다.");
    }
    $DomHasPostId.each(function (i, el) {
        var postId = $(el).data('post-id');
        $.post(MytoryBoard.ajaxUrl, {
            action: 'increase_pageview',
            nonce: MytoryBoard.ajaxNonce,
            post_id: postId
        }, function (data) {
            if (MytoryBoard.wpDebug && data.result != 1 && typeof window.console == 'object') {
                console.error("페이지뷰를 올리는 데 실패했습니다.");
            }
        }, 'json');
    });
});