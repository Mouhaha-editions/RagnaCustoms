/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
// import './src/sass/main.scss';

// start the Stimulus application
import './bootstrap';

import "./app.bundle.min";
import '../public/bundles/pagination/js/see-more.js';
// const app = require('./js/utils/core');

const copyToClipboard = str => {
    const el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
};

$(".copy-clipboard").on('click', function () {
    copyToClipboard($(this).data('to-copy'));
    return false;
})

$(function () {
    $(document).on("click", ".ajax-link", function () {
        let t = $(this);
        let action = t.data('success-action');
        $.ajax({
            url: t.data('url'),
            dataType: 'json',
            success: function (data) {
                if (data.error) {
                    alert(data.errorMessage);
                    return;
                }
                switch (action) {
                    case "replace":
                        $(t.data('replace-selector')).html(data.result);
                        break;
                }
            },
            error: function (data) {
                alert('Erreur lors de la requete');
            }

        });

        return false;
    });

    $(document).on('click', '.ask-for-confirmation', function () {
        return confirm("Your are going to delete an element definitely, do you confirm ?");
    });
    $(window).trigger('resize');

    $(document).on('click', ".song-review", function () {
        let t = $(this);

        $.ajax({
            url: t.data('url'),
            data: {
                id: t.data('song-id')
            },
            success: function (data) {

                $("#form-review").html(data.response);
                require('./js/form/form-rating');
                $(".rating-list.form-rating").on('change',function(){
                    let t = $(this);
                    console.log(t.data('input-selector'));
                    console.log($('input[name='+t.data('input-selector')+']'));
                    $('input[name='+t.data('input-selector')+']').val(t.data('rating'));
                });
                $("#form-review form").on('submit',function(){
                    let tt = $(this);
                    $.ajax({
                        url: tt.data('url'),
                        data : tt.serialize(),
                        success:function(data){
                            t.closest(t.data('replace-selector')).html(data.response);
                        }
                    });

                    $("#form-review").html("<p>Sending your review</p>");


                    return false;
                });
            }
        });
        return false;

    })

})