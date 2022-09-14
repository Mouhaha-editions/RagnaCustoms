const {RagnaBeat} = require("./ragna-beat/ragnabeat");
const Swal = require('sweetalert2/dist/sweetalert2.js');
import Swup from 'swup';
import SwupMatomoPlugin from '@swup/matomo-plugin';

const swup = new Swup({
    plugins: [new SwupMatomoPlugin()]


});
let swl = null;
$(function () {

    let maxBg = 26;
    let currentBg = Math.floor(Math.random() * maxBg-1) +1;

    var images = [];

    function preload() {
        for (var i = 1; i < maxBg; i++) {
            images[i] = new Image();
            images[i].src = '/bg/' + currentBg + '.jpg';
        }
    }
    let switchBg = function () {
        $("header").css({"background-image": 'url("/bg/' + currentBg + '.jpg")'});
        currentBg += 1;
        if (currentBg > maxBg) {
            currentBg = 1;
        }
    };

    preload();
    switchBg();

    // setInterval(switchBg, 10000);
    $(".alert").each(function () {
        Swal.fire({
            title: $(this).data('title'),
            html: $(this).html(),
            icon: $(this).data('type') === "danger" ? "error" : $(this).data('type'),
            confirmButtonText: 'close'
        });
    });
});

$(document).on('click', '[data-confirm]', function () {
    return confirm($(this).data('confirm'));
});

$(document).on('change', 'input[type="file"]', function (e) {
    let fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
});


$("[data-toggle=\"tooltip\"]").tooltip({delay: 100});

$(document).on('click', '.open-download-buttons', function () {
    let t = $(this).closest('.on-hover').find('.big-buttons');
    t.toggleClass('d-none');
    return false;
});

$(document).on('preview-ready', function (evt, p) {
    let ragnabeat = new RagnaBeat();
    if (p.type === "modal") {
        ragnabeat.enableModal();
    }
    ragnabeat.startInit(p.uid, p.file);

    $("#previewSong").on("hide.bs.modal", function () {
        ragnabeat.stopSong();
    });
});


$(document).on('click', ".ajax-load", function () {
    let t = $(this);
    let body = $(t.data('target') + " .modal-body");
    body.html("loading ...");

    $.ajax({
        url: t.data('url'),
        data: {
            id: t.data('song-id')
        },
        success: function (data) {
            body.html(data.response);
            body.find("[data-toggle=tooltip]").tooltip();
            $(".rating-list").on('change', function () {
                let t = $(this);
                $('input[name="' + t.data('input-selector') + '"]').val(t.data('rating'));
            });
            body.find("form").on('submit', function () {
                let test = true;
                $(this).find('input').each(function () {
                    if ($(this).is(".verify-voted") && ($(this).val() === undefined || $(this).val() === "")) {
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
                    type: tt.attr('method'),
                    data: tt.serialize(),
                    success: function (data) {
                        if (t.data('refresh')) {
                            window.location.reload();
                        }
                        t.closest(t.data('replace-closest-selector')).html(data.response);
                        $(t.data('replace-selector')).html(data.response);
                        $(".modal:visible").modal('hide');

                    }
                });
                body.html("<div class=\"popup-box-actions white full void\">Sending your form</div>");
                return false;
            });
        }
    });
    return false;

});


$(document).on('change', 'input[type="file"]', function (e) {
    let fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
});


function loadForm(content) {
    $("#form-edit").html(content);
    $("#form-edit form").on('submit', function () {
        $("#form-edit").html("<div class=\"popup-box-actions white full void\">Sending your form, please wait ... </div> " +
            "<div class='progress-container'><div class='progress'></div></div>");
        let tt = $(this);

        $.ajax({
            xhr: function () {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = evt.loaded / evt.total;
                        $('.progress').css({
                            width: percentComplete * 100 + '%'
                        });
                        if (percentComplete === 1) {
                            $('.progress').addClass('hide');
                        }
                    }
                }, false);
                xhr.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = evt.loaded / evt.total;
                        $('.progress').css({
                            width: percentComplete * 100 + '%'
                        });
                    }
                }, false);
                return xhr;
            },
            url: tt.attr('action'),
            data: new FormData(this),
            type: tt.attr('method'),
            processData: false,
            contentType: false,
            success: function (data) {
                console.log(data);
                if (data.goto !== undefined && data.goto !== false) {
                    window.location.href = data.goto;
                }
                if (data.reload) {
                    window.location.reload();
                }
                if (data.error === true || data.success === false) {
                    $("#form-edit").html(data.response);
                    loadForm(data.response);
                    $('.select2entity').select2entity();

                } else {
                    tt.closest(tt.data('replace-selector')).html(data.response);
                    $(tt).closest(".modal").modal('hide');
                }
            }
        });


        $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your form</div>");
        return false;
    });
}

$(document).on('click', ".ajax-modal-form", function () {
    let t = $(this);
    $(t.data('modal')).modal('show');
    $(t.data('modal')).find('.modal-title').html(t.data('title'));
    $.ajax({
        url: t.data('url'),
        success: function (data) {
            loadForm(data.response);
            $('.select2entity').select2entity();
        }
    });
    return false;

});