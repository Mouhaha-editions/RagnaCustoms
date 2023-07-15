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

function clearTootips(){
    $('[data-toggle=tooltip]').tooltip('hide');
}

$(function () {

    $(document).on('click','[data-search-prefix]', function(){
        let prefix = $(this).data('search-prefix');
        let searchElt = $("#search");

if(
    !searchElt.val().includes('mapper:') &&
    !searchElt.val().includes('title:') &&
    !searchElt.val().includes('genre:') &&
    !searchElt.val().includes('author:')
){
    searchElt.val(prefix+searchElt.val());
}
        searchElt.val(searchElt.val().replace('mapper:',prefix));
        searchElt.val(searchElt.val().replace('genre:',prefix));
        searchElt.val(searchElt.val().replace('author:',prefix));
        searchElt.val(searchElt.val().replace('title:',prefix));
        searchElt.closest('div').find('i').text($(this).text())
    });

    let openWithClick = false;
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


