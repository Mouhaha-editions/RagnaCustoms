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
                $(".rating-list.form-rating").on('change', function () {
                    let t = $(this);
                    $('input[name=' + t.data('input-selector') + ']').val(t.data('rating'));
                });
                $("#form-review form").on('submit', function () {
                    let tt = $(this);
                    $.ajax({
                        url: tt.data('url'),
                        data: tt.serialize(),
                        success: function (data) {
                            t.closest(t.data('replace-selector')).html(data.response);
                           $(".popup-box .popup-close-button").click();
                        }
                    });

                    $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your review</div>");


                    return false;
                });
            }
        });
        return false;

    })

    $(document).on('click', '.form-rating .rating', function () {
        let item = $(this);
        let t = item.closest('.form-rating');
        let ratingItems = t.find('.rating');

        let getStarsRating = function () {
            let rating = 0;
            for (let ratingItem of ratingItems) {
                if ($(ratingItem).hasClass("filled")) {
                    rating++;
                }
            }
            return rating;
        };

        let setStarsRating = function () {
            t.data('rating', getStarsRating());
            t.trigger('change');
        };

        const fillStar = function (item) {
            $(item).addClass("filled");
        };

        const emptyStar = function (item) {
            $(item).removeClass("filled");
        };

        const toggleStars = function () {

            const itemIndex = item.data('id');
            for (let i = 0; i <= itemIndex; i++) {
                fillStar(ratingItems[i]);
            }

            for (let i = itemIndex + 1; i < ratingItems.length; i++) {
                emptyStar(ratingItems[i]);
            }

            setStarsRating();
        };

        // for (const ratingItem of ratingItems) {
        //     $(ratingItem).on('click', toggleStars);
        // }
        toggleStars();
        setStarsRating();


    });

})