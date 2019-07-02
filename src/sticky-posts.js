jQuery(function ($) {
    var sticky_posts = $('[name="sticky_posts"]').val().split(',');

    $('#selected').on('click', '.js-remove', function () {
        var $row = $(this).closest('.row');
        var removedId = $row.data('id');
        sticky_posts = _.filter(sticky_posts, function (id) {
            return id != removedId;
        });
        $('[name="sticky_posts"]').val(sticky_posts.join(','));
        $row.remove();
    });

    $("#search-post").autocomplete({
        source: ajaxurl + '?action=' + typenow +'_search_post&selected=' + sticky_posts.join(','),
        minLength: 2,
        select: function (event, ui) {
            if (!ui.item.id) {
                return false;
            }
            sticky_posts.push(ui.item.id);
            $('[name="sticky_posts"]').val(sticky_posts.join(','));

            var $row = $($('#template-row').html());
            $row.data('id', ui.item.id);
            $row.find('.title').text(ui.item.value);
            $row.appendTo('#selected');

            $('#search-post').val('');
        },
        close: function (event, ui) {
            $('#search-post').val('');
        }
    });
});