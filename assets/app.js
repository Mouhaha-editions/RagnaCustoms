/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
// import './src/sass/main.scss';

// import { Tooltip, Toast, Popover } from 'bootstrap';

// start the Stimulus application
import 'bootstrap';
import './js/form-rating';
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


import 'bootstrap-switch-button/dist/bootstrap-switch-button.min';

$(function () {
    $(document).on('change','#review-global',function(){
       let rating = parseInt($(this).data('rating'));
        $('.rating-list.text-warning').each(function(){
            let t = $(this).find(".rating:eq("+(rating-1)+")").click();
        })

    });
    $(".copy-clipboard").on('click', function () {
        let t = $(this);
        copyToClipboard($(this).data('to-copy'));
        t.tooltip('enable');
        t.tooltip('show');
        setTimeout(function(){
            t.tooltip('toggleEnabled');
        },500);
        return false;
    });
$("[data-toggle=\"tooltip\"]").tooltip('enable');
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

    $(document).on('click', ".song-review", function () {
        let t = $(this);

        $.ajax({
            url: t.data('url'),
            data: {
                id: t.data('song-id')
            },
            success: function (data) {
                $("#form-review").html(data.response);
                $(".rating-list").on('change', function () {
                    let t = $(this);
                    $('input[name=' + t.data('input-selector') + ']').val(t.data('rating'));
                });
                $("#form-review form").on('submit', function () {
                    let test = true;
                    $(this).find('input').each(function () {
                        if ($(this).val() === undefined || $(this).val() === "") {
                            test = false;
                        }
                    });
                    if (!test) {
                        alert("you need to rate each property");
                        return false;
                    }

                    let tt = $(this);
                    $.ajax({
                        url: tt.data('url'),
                        data: tt.serialize(),
                        success: function (data) {
                            if (t.data('refresh')) {
                                window.location.reload();
                            }
                            t.closest(t.data('replace-selector')).html(data.response);
                            $("#reviewSong").modal('hide');
                        }
                    });

                    $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your review</div>");


                    return false;
                });
            }
        });
        return false;

    })

    $(document).on('click', ".ajax-modal-form", function () {
        let t = $(this);
        $(t.data('modal')).modal('show');
        $.ajax({
            url: t.attr('href'),
            success: function (data) {
                $("#form-edit").html(data.response);

                $("#form-edit form").on('submit', function () {

                    let tt = $(this);
                    $.ajax({
                        url: tt.attr('action'),
                        data: tt.serialize(),
                        type: tt.attr('method'),
                        success: function (data) {
                            t.closest(t.data('replace-selector')).html(data.response);
                            $(tt).closest(".modal").modal('hide');
                        }
                    });

                    $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your form</div>");


                    return false;
                });
            }
        });
        return false;

    });

    $(document).on('change', "#chkSwitch", function () {
        let body = $('body');
        if ($(this).is(':checked')) {
            body.removeClass('light');
            body.addClass('dark');
            setCookie("light-mode", "dark")
        } else {
            body.addClass('light');
            body.removeClass('dark');
            setCookie("light-mode", "light")
        }
    });

    if (getCookie("light-mode") === null) {
        let chkSwitch = $('#chkSwitch');
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            chkSwitch.attr('checked', 'checked');
            chkSwitch.trigger('change');
        }
    }
})

function setCookie(cname, cvalue) {
    var d = new Date();
    d.setTime(d.getTime() + (5000 * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return null;
}