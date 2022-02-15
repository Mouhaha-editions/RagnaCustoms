import './styles/v2.scss';
import './bootstrap';
import 'bootstrap';

window.$ = window.jQuery = $;

import '../assets/js/plugins/rating';
import '../assets/js/plugins/form_widgets';
import '../assets/js/plugins/ajax_link';
import '../assets/js/plugins/copy_to_clipboard';
import '../assets/js/plugins/modal_ajax';
import '../assets/js/plugins/playlist';
import 'select2/dist/js/select2.full.min';
require('../public/bundles/tetranzselect2entity/js/select2entity');

$(function () {
    $('.select2entity[data-autostart="true"]').select2entity();
    $('.select2').select2();

    $(document).on('click', '.open-download-buttons', function () {
        let t = $(this).closest('.on-hover').find('.big-buttons')
        t.toggleClass('d-none');
        return false;
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





