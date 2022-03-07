import './styles/common.scss';
import './bootstrap'

$(function () {

    $(document).on('click', '[data-confirm]', function () {
        return confirm($(this).data('confirm'));
    });

    // $(document).on('focusout', 'tr', function () {
    //     let t = $(this).find('.on-hover').find('.big-buttons')
    //     t.addClass('d-none');
    //     return false;
    // });
    $(document).on('mouseover', ".popover-trigger", function () {
        $(this).popover("show");
    });

    $("[data-toggle=\"tooltip\"]").tooltip('enable');

    $(document).on('change', '#review-global', function () {
        let rating = parseInt($(this).data('rating'));
        $('.rating-list.text-warning').each(function () {
            let t = $(this).find(".rating:eq(" + (rating - 1) + ")").click();
        });
    });

    $(".nav-tabs > .nav-item:first-child>a").click();
    if (window.location.pathname !== "/leaderboard") {
        let hashtag = window.location.hash;
        $("[data-target='" + hashtag + "']").click();
        $(".nav a").on('click', function () {
            window.location.hash = $(this).data("target");
        });
    }
});





