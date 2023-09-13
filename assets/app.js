/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
// import { registerVueControllerComponents } from '@symfony/ux-vue';
// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
import './bootstrap';

function clearTootips() {
    $('[data-toggle=tooltip]').tooltip('hide');
}

function selectPills(hash) {
    let regex = /-flat$/;
    console.log('NOT flat', $("a[href=\"#" + hash + "\"]").not('.active'));

}

let firstLoadDone = false;

$(function () {
    let openWithClick = false;

    let hash = window.location.hash.replace('#', '').replace('-flat', '');
    let regex = /^pills-leaderboard-(\d{1,3})$/;

    $(document).on('click', "a[href^=\"#pills-leaderboard\"]", function () {
        let hash = $(this).attr('href').replace('#', '').replace('-flat', '');

        if (!$("a[href=\"#" + hash + "\"]").is('.active')) {
            $("a[href=\"#" + hash + "\"]").trigger('click');
        }

        if (!$("a[href=\"#" + hash + "-flat\"]").is('.active')) {
            $("a[href=\"#" + hash + "-flat\"]").trigger('click');
        }
    });

    if (regex.test(hash)) {
        $("a[href=\"#" + hash + "\"]").trigger('click');
    }

    $(document).on('click', '.circle .center', function (e) {
        e.preventDefault();
        openWithClick = true;
        if (!$(this).closest('.circle').is('.open')) {
            $('.circle').removeClass('open');
        } else {
            openWithClick = false;
        }
        $(this).closest('.circle').toggleClass('open');
        clearTootips();
        return false;
    });

    $(document).on('mouseenter', '.circle .center', function (e) {
        e.preventDefault();
        if (openWithClick) {
            return;
        }
        $(this).closest('.circle').addClass('open');
        return false;
    });

    $(document).on('click', 'a', function (e) {
        let firstCharacter = $(this).attr('href').charAt(0);
        let exception = $(this).data('no-scroll') || $(this).data('no-swup');

        if (firstCharacter !== '#' && !exception) {
            $(window).scrollTop({top: 0, behavior: 'smooth'});
        }
    });

    $(document).on('mouseleave', '.circle', function (e) {
        e.preventDefault();
        clearTootips();
        if (openWithClick) {
            return;
        }
        $(this).removeClass('open');
        return false;
    });
})


